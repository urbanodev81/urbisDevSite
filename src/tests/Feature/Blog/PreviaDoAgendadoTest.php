<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;
use Spatie\Permission\Models\Role;

/*
 * Prévia de texto agendado no painel (04/11/2026). O que importa: só quem
 * cuida do blog vê, só agendado abre (publicado e rascunho dão 404), e a
 * página não vaza para buscador nem cache.
 */

beforeEach(function () {
    $this->seed(PerfisESeguranca::class);

    $this->pasta = sys_get_temp_dir().'/blog-previa-'.uniqid();
    mkdir($this->pasta);
    config(['blog.pasta' => $this->pasta]);

    $texto = fn (string $titulo, string $data, string $extra = '') => "---\ntitulo: {$titulo}\ndescricao: Descrição de {$titulo}.\ndata: {$data}\n{$extra}---\n\nCorpo de {$titulo} com conteúdo suficiente.\n";
    file_put_contents($this->pasta.'/texto-futuro.md', $texto('Texto Futuro', now()->addDays(10)->format('Y-m-d')));
    file_put_contents($this->pasta.'/texto-velho.md', $texto('Texto Velho', now()->subDays(10)->format('Y-m-d')));
    file_put_contents($this->pasta.'/texto-rascunho.md', $texto('Texto Rascunho', now()->addDays(5)->format('Y-m-d'), "rascunho: true\n"));
});

afterEach(function () {
    array_map('unlink', glob($this->pasta.'/*.md') ?: []);
    @rmdir($this->pasta);
});

function contaDaPrevia(string $perfil): User
{
    $usuario = User::create([
        'name' => 'Prévia '.$perfil,
        'email' => 'previa-'.$perfil.'@teste.local',
        'password' => 'senha-de-teste-123',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);
    $usuario->syncRoles([$perfil]);

    return $usuario;
}

it('a redação lê o texto agendado com a faixa de prévia e sem indexar', function () {
    $resposta = $this->actingAs(contaDaPrevia(Perfis::REDACAO))->get('/painel/blog/previa/texto-futuro');

    $resposta->assertOk()
        ->assertSee('Texto Futuro')
        ->assertSee('Corpo de Texto Futuro', false)
        ->assertSee('Prévia — este texto sai no site em '.now()->addDays(10)->format('d/m/Y'), false)
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    expect($resposta->headers->get('Cache-Control'))->toContain('no-store')->toContain('private');
});

it('o site público continua sem o texto agendado', function () {
    $this->get('/blog/texto-futuro')->assertNotFound();
});

it('publicado e rascunho não abrem como prévia', function (string $slug) {
    $this->actingAs(contaDaPrevia(Perfis::ADMINISTRADOR))->get('/painel/blog/previa/'.$slug)->assertNotFound();
})->with(['texto-velho', 'texto-rascunho', 'nao-existe']);

it('exige login', function () {
    $this->get('/painel/blog/previa/texto-futuro')->assertRedirect(route('login'));
});

it('quem não cuida do blog não abre a prévia nem recebe o link', function () {
    Role::create(['name' => 'sem-blog', 'guard_name' => 'web']);
    $conta = contaDaPrevia('sem-blog');

    $this->actingAs($conta)->get('/painel/blog/previa/texto-futuro')->assertForbidden();
    $this->actingAs($conta)->get('/painel')->assertOk()
        ->assertInertia(fn ($pagina) => $pagina->where('blog.agendados.0.previa', null));
});

it('o início lista os agendados com o link da prévia para a redação', function () {
    $this->actingAs(contaDaPrevia(Perfis::REDACAO))->get('/painel')->assertOk()
        ->assertInertia(fn ($pagina) => $pagina
            ->has('blog.agendados', 1)
            ->where('blog.agendados.0.slug', 'texto-futuro')
            ->where('blog.agendados.0.previa', route('painel.blog.previa', 'texto-futuro')));
});
