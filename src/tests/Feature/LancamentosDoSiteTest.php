<?php

declare(strict_types=1);

use App\Models\Auditoria;
use App\Models\Lancamento;
use App\Models\User;
use App\Support\LancamentosDoSite;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use UrbanoDev\Midia\Acoes\GuardarImagem;
use UrbanoDev\Midia\Models\Midia;

/**
 * Banner de lançamentos (bloco 4c da 246, 06/11/2026). O que se prova: a
 * migration semeia os 2 slides de sempre e a home sai igual; o que se grava
 * (com a imagem recortada em 1200×750 pelo pacote urbanodev/midia) aparece
 * na home; trocar e remover soltam o uso da imagem anterior sem apagar nada
 * (a faxina do pacote é quem põe na quarentena, 255) e nunca mexem nas
 * capturas de `public/lancamentos/`;
 * oculto some; nenhum ativo tira o banner; link e imagem fora da regra são
 * recusados; só o administrador mexe; tudo vai para a trilha; banco fora
 * mostra os 2 de sempre.
 */
beforeEach(function () {
    $this->seed(PerfisESeguranca::class);
    LancamentosDoSite::esquecer();
    Storage::fake('public');
});

function contaDosLancamentos(string $perfil): User
{
    $usuario = User::create([
        'name' => 'Conta '.$perfil,
        'email' => 'lancamentos-'.$perfil.'@teste.local',
        'password' => 'senha-de-teste-123',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);
    $usuario->syncRoles([$perfil]);

    return $usuario;
}

function lancamentoValido(array $troca = []): array
{
    return [
        'etiqueta' => 'Cliente · No ar',
        'titulo' => 'Oficina Exemplo, um site com painel.',
        'texto' => 'Agenda, serviços e fotos trocados pelo dono.',
        'url' => 'https://oficina.exemplo.com.br',
        'chamada' => 'Visitar o site',
        'secundario_url' => '',
        'secundario' => '',
        'nota' => '',
        'ativo' => true,
        'imagem_uuid' => imagemDoBanner()->uuid,
        ...$troca,
    ];
}

/** Imagem já tratada pelo pacote, como a galeria entrega ao formulário. */
function imagemDoBanner(string $perfil = 'lancamento', int $largura = 1600, int $altura = 1200): Midia
{
    return app(GuardarImagem::class)(UploadedFile::fake()->image('captura.png', $largura, $altura), perfil: $perfil);
}

it('a migration semeia os 2 slides de sempre, e a home sai como antes', function () {
    expect(Lancamento::orderBy('ordem')->pluck('titulo')->all())->toBe(array_column(LancamentosDoSite::PADRAO, 'titulo'));

    $this->get('/')->assertOk()->assertSeeInOrder([
        'id="lancamentos"',
        'Nosso produto · No ar', 'USI, a plataforma da UrbisDev para imobiliárias.', 'href="https://usi.urbisdev.tech/planos"',
        '30 dias grátis, sem cartão de crédito.', 'src="/lancamentos/usi.jpg"',
        'Imóveis Jacob, um site feito com o USI.', 'src="/lancamentos/imoveis-jacob.jpg"',
        'class="lanc-controles"',
    ], false);
});

it('o administrador adiciona com imagem, e o slide aparece na home recortado em 1200×750', function () {
    $admin = contaDosLancamentos(Perfis::ADMINISTRADOR);

    $this->actingAs($admin)->get('/painel/site/lancamentos')->assertOk()
        ->assertInertia(fn ($p) => $p->component('Painel/Lancamentos/Index')->has('lancamentos', 2)
            ->where('lancamentos.0.imagem_url', '/lancamentos/usi.jpg')->where('medida', [1200, 750]));

    $this->actingAs($admin)->post('/painel/site/lancamentos', lancamentoValido(['texto' => "D'água <b>forte</b>."]))
        ->assertRedirect()->assertSessionHasNoErrors();

    $novo = Lancamento::where('titulo', 'Oficina Exemplo, um site com painel.')->firstOrFail();
    expect($novo->ordem)->toBe(3)
        ->and($novo->secundario_url)->toBeNull()->and($novo->nota)->toBeNull()
        ->and($novo->imagem)->toStartWith('midias/')->toEndWith('.jpg')
        ->and($novo->usosDeMidia()->where('campo', 'imagem')->count())->toBe(1);
    Storage::disk('public')->assertExists($novo->imagem);
    expect(getimagesizefromstring(Storage::disk('public')->get($novo->imagem)))->toMatchArray([0 => 1200, 1 => 750]);

    $this->get('/')->assertSee('D&#039;água &lt;b&gt;forte&lt;/b&gt;.', false)
        ->assertSee('src="'.Storage::disk('public')->url($novo->imagem).'"', false);

    expect(Auditoria::where('auditavel_type', Lancamento::class)->where('evento', 'criado')->count())->toBe(1);
});

it('sem segundo link, o botão fantasma não aparece', function () {
    $admin = contaDosLancamentos(Perfis::ADMINISTRADOR);
    Lancamento::query()->delete();

    $this->actingAs($admin)->post('/painel/site/lancamentos', lancamentoValido())->assertSessionHasNoErrors();

    // Com um slide só, o banner fica parado: sem controles nem botão secundário.
    $html = $this->get('/')->assertSee('Oficina Exemplo', false)->getContent();
    preg_match('#<section id="lancamentos".*?</section>#s', $html, $banner);
    expect($banner[0])->not->toContain('btn-ghost')->not->toContain('lanc-controles');
});

it('editar sem imagem nova mantém a de antes; trocar e remover soltam o uso sem apagar o arquivo', function () {
    $admin = contaDosLancamentos(Perfis::ADMINISTRADOR);
    $this->actingAs($admin)->post('/painel/site/lancamentos', lancamentoValido())->assertSessionHasNoErrors();
    $novo = Lancamento::latest('id')->firstOrFail();
    $primeira = $novo->imagem;

    $this->actingAs($admin)->put("/painel/site/lancamentos/{$novo->id}", [...lancamentoValido(['titulo' => 'Título novo']), 'imagem_uuid' => null])
        ->assertSessionHasNoErrors();
    expect($novo->fresh()->imagem)->toBe($primeira)->and($novo->fresh()->titulo)->toBe('Título novo');

    $this->actingAs($admin)->put("/painel/site/lancamentos/{$novo->id}", lancamentoValido())->assertSessionHasNoErrors();
    $segunda = $novo->fresh()->imagem;
    expect($segunda)->not->toBe($primeira)
        ->and($novo->usosDeMidia()->with('midia')->get()->pluck('midia.caminho')->all())->toBe([$segunda]);
    Storage::disk('public')->assertExists($primeira);
    Storage::disk('public')->assertExists($segunda);

    $this->actingAs($admin)->delete("/painel/site/lancamentos/{$novo->id}")->assertRedirect();
    Storage::disk('public')->assertExists($segunda);
    expect(\UrbanoDev\Midia\Models\MidiaUso::query()->count())->toBe(0);
});

it('trocar a imagem de um slide semeado não apaga a captura de public/lancamentos', function () {
    $admin = contaDosLancamentos(Perfis::ADMINISTRADOR);
    $usi = Lancamento::where('imagem', '/lancamentos/usi.jpg')->firstOrFail();

    $this->actingAs($admin)->put("/painel/site/lancamentos/{$usi->id}", lancamentoValido())->assertSessionHasNoErrors();

    expect($usi->fresh()->imagem)->toStartWith('midias/')
        ->and(file_exists(public_path('lancamentos/usi.jpg')))->toBeTrue();

    $jacob = Lancamento::where('imagem', '/lancamentos/imoveis-jacob.jpg')->firstOrFail();
    $jacob->delete();
    expect(file_exists(public_path('lancamentos/imoveis-jacob.jpg')))->toBeTrue();
});

it('ocultar não pede imagem nova, e o slide some da home', function () {
    $admin = contaDosLancamentos(Perfis::ADMINISTRADOR);
    $jacob = Lancamento::where('titulo', 'like', 'Imóveis Jacob%')->firstOrFail();

    $this->actingAs($admin)->put("/painel/site/lancamentos/{$jacob->id}", [
        ...$jacob->only(['etiqueta', 'titulo', 'texto', 'url', 'chamada', 'secundario_url', 'secundario', 'nota']),
        'ativo' => false,
    ])->assertSessionHasNoErrors();

    expect($jacob->fresh()->imagem)->toBe('/lancamentos/imoveis-jacob.jpg');
    $this->get('/')->assertDontSee('Imóveis Jacob, um site feito com o USI.')->assertSee('USI, a plataforma da UrbisDev');
});

it('sem nenhum lançamento ativo, o banner some sem quebrar a página', function () {
    Lancamento::query()->update(['ativo' => false]);
    LancamentosDoSite::esquecer();

    $this->get('/')->assertOk()->assertDontSee('id="lancamentos"', false)->assertSee('id="fazemos"', false);
});

it('subir e descer trocam a posição com o vizinho', function () {
    $admin = contaDosLancamentos(Perfis::ADMINISTRADOR);
    $jacob = Lancamento::where('titulo', 'like', 'Imóveis Jacob%')->firstOrFail();

    $this->actingAs($admin)->post("/painel/site/lancamentos/{$jacob->id}/mover", ['direcao' => 'subir'])->assertRedirect();
    $this->get('/')->assertSeeInOrder(['Imóveis Jacob, um site feito com o USI.', 'USI, a plataforma da UrbisDev']);

    $this->actingAs($admin)->post("/painel/site/lancamentos/{$jacob->id}/mover", ['direcao' => 'para-o-lado'])
        ->assertSessionHasErrors('direcao');
});

it('recusa link sem https, segundo link sem texto e imagem pequena, fora do formato ou ausente', function () {
    $admin = contaDosLancamentos(Perfis::ADMINISTRADOR);

    $this->actingAs($admin)->post('/painel/site/lancamentos', lancamentoValido([
        'url' => 'javascript:alert(1)', 'secundario_url' => 'http://exemplo.com', 'secundario' => 'Ver',
    ]))->assertSessionHasErrors(['url', 'secundario_url']);

    $this->actingAs($admin)->post('/painel/site/lancamentos', lancamentoValido(['secundario_url' => 'https://exemplo.com']))
        ->assertSessionHasErrors('secundario');

    // Imagem de outro perfil (sem o recorte do banner), uuid que não existe, ou nenhuma.
    $this->actingAs($admin)->post('/painel/site/lancamentos', lancamentoValido(['imagem_uuid' => imagemDoBanner('foto')->uuid]))
        ->assertSessionHasErrors('imagem_uuid');
    $this->actingAs($admin)->post('/painel/site/lancamentos', lancamentoValido(['imagem_uuid' => '0196f3a1-0000-7000-8000-000000000001']))
        ->assertSessionHasErrors('imagem_uuid');
    $this->actingAs($admin)->post('/painel/site/lancamentos', lancamentoValido(['imagem_uuid' => null]))
        ->assertSessionHasErrors('imagem_uuid');

    // O pacote recusa a imagem pequena demais para o banner e o SVG.
    $this->actingAs($admin)->postJson('/painel/midia/imagens', ['arquivo' => UploadedFile::fake()->image('p.png', 800, 500), 'perfil' => 'lancamento'])
        ->assertUnprocessable()->assertJsonValidationErrors('arquivo');
    $this->actingAs($admin)->postJson('/painel/midia/imagens', ['arquivo' => UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>'), 'perfil' => 'lancamento'])
        ->assertUnprocessable()->assertJsonValidationErrors('arquivo');

    expect(Lancamento::count())->toBe(2);
});

it('a redação não abre nem grava lançamentos', function () {
    $redacao = contaDosLancamentos(Perfis::REDACAO);
    $um = Lancamento::firstOrFail();

    $this->actingAs($redacao)->get('/painel/site/lancamentos')->assertForbidden();
    $this->actingAs($redacao)->post('/painel/site/lancamentos', lancamentoValido())->assertForbidden();
    $this->actingAs($redacao)->put("/painel/site/lancamentos/{$um->id}", lancamentoValido())->assertForbidden();
    $this->actingAs($redacao)->post("/painel/site/lancamentos/{$um->id}/mover", ['direcao' => 'descer'])->assertForbidden();
    $this->actingAs($redacao)->delete("/painel/site/lancamentos/{$um->id}")->assertForbidden();
});

it('com o banco fora, a home mostra os 2 slides de sempre', function () {
    Lancamento::where('titulo', 'like', 'Imóveis Jacob%')->update(['titulo' => 'Título só do banco']);
    LancamentosDoSite::esquecer();

    config([
        'database.connections.sem_banco' => [...config('database.connections.pgsql'), 'host' => '127.0.0.1', 'port' => 1],
        'database.default' => 'sem_banco',
    ]);

    try {
        $this->get('/')->assertOk()->assertSee('Imóveis Jacob, um site feito com o USI.')->assertDontSee('Título só do banco')
            ->assertSee('src="/lancamentos/usi.jpg"', false);
    } finally {
        config(['database.default' => 'pgsql']);
    }
});
