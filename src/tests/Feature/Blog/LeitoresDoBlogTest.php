<?php

declare(strict_types=1);

use App\Blog\Leitores;
use App\Models\BlogComentario;
use App\Models\BlogVoto;
use App\Models\User;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as UsuarioSocialite;

/*
 * Leitores do blog (bloco 3b da 246, 04/11/2026). O que importa:
 * - a leitura conta uma vez por sessão, sem robô e sem slug inventado;
 * - o "ajudou?" funciona sem JS e aceita o "o que faltou" no mesmo voto;
 * - comentário só aparece depois de aprovado, e o e-mail nunca vai à página;
 * - link no comentário é recusado; o captcha vale para o formulário, não
 *   para quem entrou pelo LinkedIn;
 * - sem as chaves do LinkedIn, botão e rotas não existem;
 * - a moderação é do perfil com `blog.gerenciar`.
 */

const NAVEGADOR = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

beforeEach(function () {
    $this->seed(PerfisESeguranca::class);
    Cache::flush();

    $this->pasta = sys_get_temp_dir().'/blog-leitores-'.uniqid();
    mkdir($this->pasta);
    config(['blog.pasta' => $this->pasta]);
    foreach (['primeiro' => '2026-01-10', 'segundo' => '2026-01-11', 'terceiro' => '2026-01-12'] as $slug => $data) {
        file_put_contents("{$this->pasta}/{$slug}.md", "---\ntitulo: Texto {$slug}\ndescricao: Resumo {$slug}.\ndata: {$data}\n---\n\nCorpo do {$slug}.\n");
    }
});

afterEach(function () {
    array_map('unlink', glob($this->pasta.'/*.md') ?: []);
    @rmdir($this->pasta);
});

function moderador(string $perfil = Perfis::REDACAO): User
{
    $u = User::create([
        'name' => 'Moderação '.$perfil,
        'email' => 'mod-'.$perfil.'-'.uniqid().'@teste.local',
        'password' => 'senha-de-teste-123',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);
    $u->syncRoles([$perfil]);

    return $u;
}

function comentarioValido(array $mais = []): array
{
    return [
        'nome' => 'Maria Souza',
        'email' => 'maria@exemplo.com.br',
        'corpo' => 'Muito bom, me ajudou a decidir entre as duas opções.',
        'consent' => '1',
        'captcha_token' => CAPTCHA_DE_TESTE,
        ...$mais,
    ];
}

function leituras(string $slug): int
{
    return (int) DB::table('blog_leituras')->where('slug', $slug)->sum('total');
}

it('conta a leitura uma vez por sessão e ignora robô e slug que não existe', function () {
    $this->withHeader('User-Agent', NAVEGADOR)->post('/blog/primeiro/lido')->assertNoContent();
    $this->withHeader('User-Agent', NAVEGADOR)->post('/blog/primeiro/lido')->assertNoContent();
    expect(leituras('primeiro'))->toBe(1);

    $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1)')->flushSession()->post('/blog/primeiro/lido');
    $this->withHeader('User-Agent', 'curl/8.5')->flushSession()->post('/blog/primeiro/lido');
    $this->withHeader('User-Agent', NAVEGADOR)->flushSession()->post('/blog/nao-existe/lido')->assertNoContent();

    expect(leituras('primeiro'))->toBe(1)
        ->and(DB::table('blog_leituras')->where('slug', 'nao-existe')->exists())->toBeFalse();

    // Outra pessoa (outra sessão) conta de novo, no mesmo agregado do dia.
    $this->withHeader('User-Agent', NAVEGADOR)->flushSession()->post('/blog/primeiro/lido');
    expect(leituras('primeiro'))->toBe(2)
        ->and(DB::table('blog_leituras')->count())->toBe(1);
});

it('a página do texto traz o aviso de leitura, o ajudou, os comentários e o WhatsApp', function () {
    $this->get('/blog/primeiro')->assertOk()
        ->assertSee('primeiro\\/lido', false)
        ->assertSee('Este texto ajudou?')
        ->assertSee('Deixe o seu comentário')
        ->assertSee('Fale com a gente')
        ->assertSee(rawurlencode('Li o texto "Texto primeiro"'), false)
        // Sem as chaves do LinkedIn, o botão não aparece.
        ->assertDontSee('Comentar com o LinkedIn');
});

it('mostra os mais lidos só na entrada do blog', function () {
    DB::table('blog_leituras')->insert([
        ['slug' => 'primeiro', 'dia' => now()->toDateString(), 'total' => 3],
        ['slug' => 'segundo', 'dia' => now()->toDateString(), 'total' => 9],
        ['slug' => 'apagado', 'dia' => now()->toDateString(), 'total' => 50],
        ['slug' => 'terceiro', 'dia' => now()->subDays(40)->toDateString(), 'total' => 99],
    ]);

    $lista = app(Leitores::class)->maisLidos();
    expect(array_map(fn ($i) => $i['post']->slug, $lista))->toBe(['segundo', 'primeiro']);

    $this->get('/blog')->assertSee('Mais lidos do mês');
    $this->get('/blog?q=texto')->assertDontSee('Mais lidos do mês');
});

it('o voto vale uma vez e o "não" aceita o que faltou depois', function () {
    $this->post('/blog/primeiro/ajudou', ['util' => 'nao'])->assertRedirect(url('/blog/primeiro').'#ajudou');
    $this->get('/blog/primeiro')->assertSee('O que faltou?');

    $this->post('/blog/primeiro/ajudou', ['faltou' => 'Um exemplo com números <b>reais</b> https://spam.example']);
    $this->post('/blog/primeiro/ajudou', ['util' => 'sim']);

    $voto = BlogVoto::sole();
    expect($voto->util)->toBeFalse()
        ->and($voto->faltou)->toBe('Um exemplo com números reais');
    $this->get('/blog/primeiro')->assertSee('Você já respondeu');
});

it('o comentário entra na fila, avisa a equipe e só aparece depois de aprovado', function () {
    $this->post('/blog/primeiro/comentarios', comentarioValido())
        ->assertRedirect(url('/blog/primeiro').'#comentar')
        ->assertSessionHas('blog.comentario', 'recebido');

    $c = BlogComentario::sole();
    expect($c->status)->toBe(BlogComentario::PENDENTE)
        ->and($c->consentimento_em)->not->toBeNull()
        ->and($c->email_enviado_em)->not->toBeNull();
    $this->get('/blog/primeiro')->assertDontSee('me ajudou a decidir');

    $this->actingAs(moderador())->put(route('painel.blog.comentarios.update', $c->uuid), ['status' => 'aprovado'])->assertRedirect();
    $this->actingAs(moderador())->put(route('painel.blog.comentarios.update', $c->uuid), ['resposta' => 'Obrigado, Maria!']);

    $this->get('/blog/primeiro')
        ->assertSee('me ajudou a decidir')
        ->assertSee('Maria Souza')
        ->assertSee('MS')
        ->assertSee('Obrigado, Maria!')
        ->assertDontSee('maria@exemplo.com.br');

    expect($c->fresh()->moderado_por)->not->toBeNull();
});

it('recusa comentário com link, sem captcha ou sem aceite', function (array $dados, string $campo) {
    $this->post('/blog/primeiro/comentarios', comentarioValido($dados))
        ->assertRedirect(url('/blog/primeiro').'#comentar')
        ->assertSessionHasErrors($campo, null, 'comentario');

    expect(BlogComentario::count())->toBe(0);
})->with([
    'link' => [['corpo' => 'Veja mais em https://golpe.example agora mesmo'], 'corpo'],
    'domínio solto' => [['corpo' => 'Compre seguidores em seguidores.com.br hoje'], 'corpo'],
    'captcha errado' => [['captcha_token' => 'errado'], 'captcha_token'],
    'sem aceite' => [['consent' => null], 'consent'],
]);

it('o clique repetido não cria dois comentários', function () {
    $this->post('/blog/primeiro/comentarios', comentarioValido());
    $this->post('/blog/primeiro/comentarios', comentarioValido());

    expect(BlogComentario::count())->toBe(1);
});

it('sem as chaves do LinkedIn, as rotas dão 404', function () {
    $this->get('/blog/entrar/linkedin?volta=primeiro')->assertNotFound();
    $this->get('/blog/entrar/linkedin/retorno')->assertNotFound();
});

it('quem entra pelo LinkedIn comenta sem captcha, com selo, e ainda passa pela fila', function () {
    config(['services.linkedin-openid.client_id' => 'id', 'services.linkedin-openid.client_secret' => 'segredo']);

    $this->get('/blog/primeiro')->assertSee('Comentar com o LinkedIn');

    $usuario = (new UsuarioSocialite)->map(['id' => 'abc123', 'name' => 'João Lima', 'email' => 'joao@exemplo.com', 'avatar' => 'https://media.licdn.com/foto.jpg']);
    Socialite::shouldReceive('driver->user')->andReturn($usuario);

    $this->withSession(['blog.linkedin_volta' => 'primeiro'])
        ->get('/blog/entrar/linkedin/retorno')
        ->assertRedirect(url('/blog/primeiro').'#comentar');

    $this->get('/blog/primeiro')->assertSee('Comentando como')->assertSee('João Lima');

    $this->post('/blog/primeiro/comentarios', ['corpo' => 'Comentário de quem entrou pelo LinkedIn.', 'consent' => '1'])
        ->assertSessionHas('blog.comentario', 'recebido');

    $c = BlogComentario::sole();
    expect($c->origem)->toBe(BlogComentario::ORIGEM_LINKEDIN)
        ->and($c->linkedin_sub)->toBe('abc123')
        ->and($c->email)->toBe('joao@exemplo.com')
        ->and($c->status)->toBe(BlogComentario::PENDENTE);

    $c->update(['status' => BlogComentario::APROVADO]);
    $this->get('/blog/primeiro')->assertSee('verificado pelo LinkedIn')->assertDontSee('media.licdn.com');

    $this->post('/blog/entrar/linkedin/sair', ['volta' => 'primeiro']);
    $this->get('/blog/primeiro')->assertDontSee('Comentando como');
});

it('a moderação e os leitores são de quem cuida do blog', function () {
    $c = BlogComentario::create(['slug' => 'primeiro', 'nome' => 'X', 'email' => 'x@x.com', 'corpo' => 'Um comentário qualquer aqui.']);
    BlogVoto::create(['slug' => 'primeiro', 'util' => false, 'faltou' => 'Faltou exemplo']);
    DB::table('blog_leituras')->insert([
        ['slug' => 'primeiro', 'dia' => now()->toDateString(), 'total' => 4],
        ['slug' => 'agendado-ou-apagado', 'dia' => now()->toDateString(), 'total' => 7],
    ]);

    $this->get(route('painel.blog.comentarios.index'))->assertRedirect(route('login'));

    $this->actingAs(moderador())->get(route('painel.blog.comentarios.index'))->assertOk()
        ->assertInertia(fn ($p) => $p->component('Painel/Blog/Comentarios')->where('pendentes', 1)->has('comentarios.data', 1));

    $this->actingAs(moderador(Perfis::ADMINISTRADOR))->get(route('painel.blog.leitores.index'))->assertOk()
        ->assertInertia(fn ($p) => $p->component('Painel/Blog/Leitores')
            ->where('totais.hoje', 4)
            ->where('textos.0.slug', 'primeiro')
            ->where('textos.0.votos.pct', 0)
            ->where('faltou.0.texto', 'Faltou exemplo'));

    $this->actingAs(moderador())->delete(route('painel.blog.comentarios.destroy', $c->uuid));
    expect($c->fresh()->trashed())->toBeTrue();
    $this->actingAs(moderador())->patch(route('painel.blog.comentarios.restaurar', $c->uuid));
    expect($c->fresh()->trashed())->toBeFalse();
});

it('o expurgo leva o comentário recusado antigo e mantém o aprovado', function () {
    $velho = now()->subMonths(30);
    $recusado = BlogComentario::create(['slug' => 'primeiro', 'nome' => 'A', 'email' => 'a@a.com', 'corpo' => 'Recusado antigo.', 'status' => 'recusado']);
    $aprovado = BlogComentario::create(['slug' => 'primeiro', 'nome' => 'B', 'email' => 'b@b.com', 'corpo' => 'Aprovado antigo.', 'status' => 'aprovado']);
    BlogComentario::query()->update(['created_at' => $velho]);

    $this->artisan('lgpd:expurgo')->assertSuccessful();

    expect(BlogComentario::withTrashed()->find($recusado->id))->toBeNull()
        ->and(BlogComentario::find($aprovado->id))->not->toBeNull();
});
