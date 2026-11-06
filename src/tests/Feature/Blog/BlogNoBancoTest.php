<?php

declare(strict_types=1);

use App\Blog\SaneadorDeTexto;
use App\Models\Auditoria;
use App\Models\Texto;
use App\Models\User;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
 * Blog no banco (bloco 3a da 246, 04/11/2026). O que importa:
 * - o servidor saneia o corpo, porque o editor não é barreira;
 * - texto do painel segue as mesmas regras de rascunho e data que o .md segue;
 * - o banco ganha do .md com o mesmo slug, e sem banco os .md seguem no ar;
 * - a imagem é reencodada: sem EXIF, reduzida, e a capa sai 1200×630;
 * - o endereço trava depois de ir ao ar.
 */

beforeEach(function () {
    $this->seed(PerfisESeguranca::class);
    Storage::fake('public');

    $this->pasta = sys_get_temp_dir().'/blog-banco-'.uniqid();
    mkdir($this->pasta);
    config(['blog.pasta' => $this->pasta]);
    file_put_contents($this->pasta.'/do-arquivo.md', "---\ntitulo: Do Arquivo\ndescricao: Texto que mora no md.\ndata: 2026-01-10\ntags: docker\n---\n\nCorpo **do arquivo**.\n\n<script>alert(1)</script>\n");
});

afterEach(function () {
    array_map('unlink', glob($this->pasta.'/*.md') ?: []);
    @rmdir($this->pasta);
});

function contaDoBlog(?string $perfil = Perfis::REDACAO): User
{
    $usuario = User::create([
        'name' => 'Blog '.($perfil ?? 'sem perfil'),
        'email' => 'blog-'.($perfil ?? 'nada').'-'.uniqid().'@teste.local',
        'password' => 'senha-de-teste-123',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);
    if ($perfil !== null) {
        $usuario->syncRoles([$perfil]);
    }

    return $usuario;
}

function textoValido(array $mais = []): array
{
    return [
        'titulo' => 'Como escolher um sistema',
        'descricao' => 'Resumo para o Google.',
        'corpo_html' => '<h2>O problema</h2><p>Texto com <strong>negrito</strong>.</p>',
        'data_publicacao' => now()->format('Y-m-d'),
        'rascunho' => false,
        'tags' => ['gestão', ' software '],
        ...$mais,
    ];
}

/** JPEG de verdade com um bloco EXIF (e um "GPS" marcado) logo depois do início. */
function fotoComExif(int $largura, int $altura): UploadedFile
{
    $img = imagecreatetruecolor($largura, $altura);
    imagefill($img, 0, 0, imagecolorallocate($img, 200, 80, 40));
    ob_start();
    imagejpeg($img);
    $jpeg = (string) ob_get_clean();

    $exif = "Exif\0\0MM\0*\0\0\0\x08\0\0\0\0\0\0GPS-SEGREDO-DA-CASA";
    $app1 = "\xFF\xE1".pack('n', strlen($exif) + 2).$exif;
    $caminho = tempnam(sys_get_temp_dir(), 'foto').'.jpg';
    file_put_contents($caminho, substr($jpeg, 0, 2).$app1.substr($jpeg, 2));

    return new UploadedFile($caminho, 'IMG_2026_casa.jpg', 'image/jpeg', null, true);
}

describe('saneador', function () {
    it('tira script, evento, iframe cru, link javascript e imagem de fora', function () {
        $limpo = app(SaneadorDeTexto::class)->limpar(
            '<p onclick="x()">oi</p><script>alert(1)</script><iframe src="https://mal.example"></iframe>'
            .'<a href="javascript:alert(1)">mau</a><img src="https://mal.example/a.png" alt="x">'
            .'<img src="/storage/blog/2026/11/0193a1b2-c3d4-7e5f-8a9b-0c1d2e3f4a5b.webp" alt="nossa">'
        );

        expect($limpo)->toContain('<p>oi</p>')
            ->not->toContain('script')->not->toContain('onclick')->not->toContain('iframe')
            ->not->toContain('javascript')->not->toContain('mal.example')
            ->toContain('alt="nossa"');
    });

    it('vídeo só vira iframe sem cookie na leitura, e id torto some', function () {
        $s = app(SaneadorDeTexto::class);
        $limpo = $s->limpar('<figure data-video="youtube" data-id="dQw4w9WgXcQ"><p>lixo</p></figure>'
            .'<figure data-video="youtube" data-id="x&quot;onload=1"></figure><figure data-video="dailymotion" data-id="abc"></figure>'
            .'<figure data-video="vimeo" data-id="76979871"></figure>');

        expect($limpo)->toBe('<figure data-video="youtube" data-id="dQw4w9WgXcQ"></figure><figure data-video="vimeo" data-id="76979871"></figure>');
        expect($s->paraLeitura($limpo))
            ->toContain('src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ"')
            ->toContain('src="https://player.vimeo.com/video/76979871?dnt=1"')
            ->toContain('loading="lazy"');
    });
});

it('a redação escreve um texto e ele vai ao ar saneado, com tags limpas', function () {
    $this->actingAs(contaDoBlog())
        ->post('/painel/blog/textos', textoValido(['corpo_html' => '<p>Bom.</p><script>alert(1)</script>']))
        ->assertRedirect();

    $texto = Texto::sole();
    expect($texto->slug)->toBe('como-escolher-um-sistema')
        ->and($texto->corpo_html)->toBe('<p>Bom.</p>')
        ->and($texto->tags)->toBe(['gestão', 'software']);

    $this->get('/blog/como-escolher-um-sistema')->assertOk()->assertSee('<p>Bom.</p>', false)->assertDontSee('alert(1)', false);
    $this->get('/blog')->assertSee('Como escolher um sistema')->assertSee('Do Arquivo');
});

it('rascunho e agendado não aparecem no site, mas abrem na prévia do painel', function (array $mais, string $faixa) {
    $conta = contaDoBlog();
    $this->actingAs($conta)->post('/painel/blog/textos', textoValido($mais));
    $texto = Texto::sole();

    auth()->logout();
    $this->get('/blog/'.$texto->slug)->assertNotFound();

    $this->actingAs($conta)->get('/painel/blog/textos/'.$texto->uuid.'/previa')
        ->assertOk()->assertSee($faixa, false)->assertHeader('X-Robots-Tag', 'noindex, nofollow');
})->with([
    'rascunho' => [['rascunho' => true], 'rascunho: ainda não tem data'],
    'agendado' => [['data_publicacao' => now()->addDays(3)->format('Y-m-d')], 'sai no site em '.now()->addDays(3)->format('d/m/Y')],
]);

it('não deixa criar texto com o endereço de um .md, mas o importado ganha do arquivo', function () {
    $this->actingAs(contaDoBlog())
        ->post('/painel/blog/textos', textoValido(['slug' => 'do-arquivo']))
        ->assertSessionHasErrors('slug');

    $this->artisan('blog:importar')->assertSuccessful();
    expect(Texto::count())->toBe(0);

    $this->artisan('blog:importar', ['--executar' => true])->assertSuccessful();
    $texto = Texto::sole();
    expect($texto->origem)->toBe('arquivo')
        ->and($texto->corpo_html)->toContain('<strong>do arquivo</strong>')->not->toContain('script');

    $texto->update(['titulo' => 'Do Banco Agora']);
    $this->get('/blog/do-arquivo')->assertOk()->assertSee('Do Banco Agora');

    $this->artisan('blog:importar', ['--executar' => true])->expectsOutputToContain('já está no banco');
    expect(Texto::count())->toBe(1);
});

it('o endereço trava depois de ir ao ar', function () {
    $conta = contaDoBlog();
    $this->actingAs($conta)->post('/painel/blog/textos', textoValido(['rascunho' => true]));
    $texto = Texto::sole();

    $this->put('/painel/blog/textos/'.$texto->uuid, textoValido(['rascunho' => true, 'slug' => 'novo-endereco']))->assertSessionHasNoErrors();
    expect($texto->fresh()->slug)->toBe('novo-endereco');

    $this->put('/painel/blog/textos/'.$texto->uuid, textoValido(['slug' => 'novo-endereco']))->assertSessionHasNoErrors();
    $this->put('/painel/blog/textos/'.$texto->uuid, textoValido(['slug' => 'outro-endereco']))->assertSessionHasErrors('slug');
    expect($texto->fresh()->slug)->toBe('novo-endereco');
});

it('arquivar tira do site e restaurar devolve', function () {
    $conta = contaDoBlog();
    $this->actingAs($conta)->post('/painel/blog/textos', textoValido());
    $texto = Texto::sole();

    $this->delete('/painel/blog/textos/'.$texto->uuid)->assertRedirect();
    $this->get('/blog/'.$texto->slug)->assertNotFound();

    $this->patch('/painel/blog/textos/'.$texto->uuid.'/restaurar')->assertRedirect();
    $this->get('/blog/'.$texto->slug)->assertOk();
});

it('corpo vazio ou só com tag é recusado', function () {
    $this->actingAs(contaDoBlog())
        ->post('/painel/blog/textos', textoValido(['corpo_html' => '<p><script>x</script></p>']))
        ->assertSessionHasErrors('corpo_html');
});

it('na trilha, mudar o corpo registra que mudou, sem copiar o texto', function () {
    $conta = contaDoBlog();
    $this->actingAs($conta)->post('/painel/blog/textos', textoValido());
    $texto = Texto::sole();

    $this->put('/painel/blog/textos/'.$texto->uuid, textoValido(['corpo_html' => '<p>Outro corpo inteiro.</p>']));

    $linha = Auditoria::query()->where('campo', 'corpo_html')->sole();
    expect($linha->de)->toBeNull()->and($linha->para)->toBeNull();
});

it('quem não tem o blog não entra', function () {
    $this->actingAs(contaDoBlog(null))->get('/painel/blog/textos')->assertForbidden();
    $this->post('/painel/blog/imagens', ['arquivo' => fotoComExif(400, 300)])->assertForbidden();
});

it('a imagem sai em WebP, sem EXIF e com no máximo 1600 px', function () {
    $resposta = $this->actingAs(contaDoBlog())
        ->post('/painel/blog/imagens', ['arquivo' => fotoComExif(3200, 1800)], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJson(['largura' => 1600, 'altura' => 900]);

    $caminho = substr($resposta->json('url'), strlen('/storage/'));
    expect($caminho)->toMatch('~^blog/\d{4}/\d{2}/[0-9a-f-]{36}\.webp$~');

    $bytes = Storage::disk('public')->get($caminho);
    expect(substr($bytes, 0, 4))->toBe('RIFF')->and(substr($bytes, 8, 4))->toBe('WEBP')
        ->and($bytes)->not->toContain('GPS-SEGREDO')->not->toContain('Exif');
});

it('a galeria lista as imagens já enviadas, da mais nova para a mais velha', function () {
    $conta = contaDoBlog();
    $primeira = $this->actingAs($conta)->post('/painel/blog/imagens', ['arquivo' => fotoComExif(800, 600)], ['Accept' => 'application/json'])->json('url');
    $segunda = $this->post('/painel/blog/imagens', ['arquivo' => fotoComExif(1000, 1000), 'capa' => '1'], ['Accept' => 'application/json'])->json('url');

    $this->getJson('/painel/blog/imagens')
        ->assertOk()
        ->assertJsonPath('imagens.0.url', $segunda)
        ->assertJsonPath('imagens.0.capa', true)
        ->assertJsonPath('imagens.1.url', $primeira)
        ->assertJsonPath('imagens.1.largura', 800);

    $this->actingAs(contaDoBlog(null))->getJson('/painel/blog/imagens')->assertForbidden();
});

it('a galeria junta as ilustrações dos guias, as capas e as imagens de dentro dos textos, com onde foram usadas', function () {
    $conta = contaDoBlog();
    $url = $this->actingAs($conta)->post('/painel/blog/imagens', ['arquivo' => fotoComExif(800, 600)], ['Accept' => 'application/json'])->json('url');
    $this->post('/painel/blog/textos', textoValido([
        'titulo' => 'Texto com as duas',
        'imagem' => '/img/guias/backup.jpg', 'imagem_alt' => 'Servidor e backup',
        'corpo_html' => '<p>Oi.</p><img src="'.$url.'" alt="Gráfico do mês" data-tamanho="media" data-alinhar="direita">',
    ]));

    $imagens = collect($this->getJson('/painel/blog/imagens')->assertOk()->json('imagens'))->keyBy('url');

    expect($imagens['/img/guias/backup.jpg'])->toMatchArray(['capa' => true, 'usos' => ['Texto com as duas'], 'alt' => 'Servidor e backup'])
        ->and($imagens['/img/guias/backup.jpg']['largura'])->toBeGreaterThan(0)
        ->and($imagens[$url])->toMatchArray(['usos' => ['Texto com as duas'], 'alt' => 'Gráfico do mês'])
        ->and($imagens->keys())->toContain('/img/guias/captcha.jpg');
});

it('a imagem pequena à direita chega ao site com o atributo e o estilo', function () {
    $this->actingAs(contaDoBlog())->post('/painel/blog/textos', textoValido([
        'corpo_html' => '<p>Antes.</p><img src="/img/guias/deploy.jpg" alt="O deploy" data-tamanho="pequena" data-alinhar="direita"><p>Depois.</p>',
    ]));

    $this->get('/blog/como-escolher-um-sistema')->assertOk()
        ->assertSee('data-tamanho="pequena"', false)
        ->assertSee('data-alinhar="direita"', false)
        ->assertSee('.blog-texto img[data-alinhar="direita"]', false);
});

it('tamanho e alinhamento da imagem passam, valor inventado sai', function () {
    $saneador = app(\App\Blog\SaneadorDeTexto::class);

    expect($saneador->limpar('<img src="/img/guias/deploy.jpg" alt="a" data-tamanho="pequena" data-alinhar="esquerda">'))
        ->toContain('data-tamanho="pequena"')->toContain('data-alinhar="esquerda"')->toContain('/img/guias/deploy.jpg')
        ->and($saneador->limpar('<img src="/img/guias/deploy.jpg" alt="a" data-tamanho="gigante" data-alinhar="x" onerror="y">'))
        ->not->toContain('data-tamanho')->not->toContain('data-alinhar')->not->toContain('onerror')
        ->and($saneador->limpar('<img src="/img/guias/../../.env" alt="a"><img src="https://fora.com/x.jpg" alt="b">'))
        ->not->toContain('<img');
});

it('ampliar ao clicar: só "sim" passa e o site leva a caixa da foto grande', function () {
    $saneador = app(\App\Blog\SaneadorDeTexto::class);

    expect($saneador->limpar('<img src="/img/guias/deploy.jpg" alt="a" data-ampliar="sim">'))->toContain('data-ampliar="sim"')
        ->and($saneador->limpar('<img src="/img/guias/deploy.jpg" alt="a" data-ampliar="javascript:x">'))->not->toContain('data-ampliar');

    $this->actingAs(contaDoBlog())->post('/painel/blog/textos', textoValido([
        'corpo_html' => '<p>Antes.</p><img src="/img/guias/deploy.jpg" alt="O deploy" data-ampliar="sim"><p>Depois.</p>',
    ]));

    $this->get('/blog/como-escolher-um-sistema')->assertOk()
        ->assertSee('data-ampliar="sim"', false)
        ->assertSee('id="foto-ampliada"', false)
        ->assertSee('img[data-ampliar="sim"]', false);
});

it('a capa sai recortada em 1200×630', function () {
    $this->actingAs(contaDoBlog())
        ->post('/painel/blog/imagens', ['arquivo' => fotoComExif(1000, 1000), 'capa' => '1'], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJson(['largura' => 1200, 'altura' => 630]);
});

it('recusa imagem acima de 25 megapixels sem tentar abrir', function () {
    $this->actingAs(contaDoBlog())
        ->post('/painel/blog/imagens', ['arquivo' => fotoComExif(6000, 5000)], ['Accept' => 'application/json'])
        ->assertUnprocessable()->assertJsonValidationErrors('arquivo');
});

it('recusa arquivo que não é imagem', function () {
    $this->actingAs(contaDoBlog())
        ->post('/painel/blog/imagens', ['arquivo' => UploadedFile::fake()->create('virus.php', 10, 'application/x-php')], ['Accept' => 'application/json'])
        ->assertUnprocessable();
});

it('a CSP libera só o YouTube sem cookie e o Vimeo em frame', function () {
    $csp = $this->get('/blog')->headers->get('Content-Security-Policy');

    expect($csp)->toContain('frame-src https://www.youtube-nocookie.com https://player.vimeo.com;');
});

it('sem banco, o .md segue no ar', function () {
    config([
        'database.connections.sem_banco' => [...config('database.connections.pgsql'), 'host' => '127.0.0.1', 'port' => 1],
        'database.default' => 'sem_banco',
    ]);

    $this->get('/blog')->assertOk()->assertSee('Do Arquivo');
    $this->get('/blog/do-arquivo')->assertOk();

    config(['database.default' => 'pgsql']);
});
