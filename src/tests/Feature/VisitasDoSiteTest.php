<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

/**
 * Visitas do site (09/11/2026, pendência 263), pelo pacote urbanodev/visitas.
 * O pacote tem a própria suíte (robô, prefetch, sal do dia, escopo); aqui se
 * prova o que é do urbisdev: QUAIS páginas contam e quem vê o número.
 */
beforeEach(function () {
    $this->seed(PerfisESeguranca::class);
});

const NAVEGADOR_DE_VERDADE = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

function contaDeVisitas(string $perfil): User
{
    $usuario = User::create([
        'name' => 'Visitas '.$perfil,
        'email' => $perfil.'@visitas.local',
        'password' => 'senha-de-teste-123',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);
    $usuario->syncRoles([$perfil]);

    return $usuario;
}

it('conta as páginas do site e do blog', function (string $url) {
    $this->withHeader('User-Agent', NAVEGADOR_DE_VERDADE)->get($url)->assertOk();

    expect((int) DB::table('visitas_dias')->sum('visualizacoes'))->toBe(1);
})->with(['/', '/como-trabalhamos', '/privacidade', '/demonstracoes', '/blog']);

it('não conta sitemap, llms.txt, captcha nem o painel', function (string $url) {
    $this->withHeader('User-Agent', NAVEGADOR_DE_VERDADE)->get($url);

    expect(DB::table('visitas_paginas')->count())->toBe(0);
})->with(['/sitemap.xml', '/llms.txt', '/captcha/desafio', '/painel/login']);

it('quem está logado no painel não conta ao abrir o site', function () {
    $this->actingAs(contaDeVisitas(Perfis::ADMINISTRADOR))
        ->withHeader('User-Agent', NAVEGADOR_DE_VERDADE)->get('/')->assertOk();

    expect(DB::table('visitas_dias')->count())->toBe(0);
});

it('robô não entra na visita e aparece como descartado', function () {
    $this->withHeader('User-Agent', 'WhatsApp/2.23.20.0')->get('/')->assertOk();

    expect((int) DB::table('visitas_dias')->value('visualizacoes'))->toBe(0)
        ->and((int) DB::table('visitas_dias')->value('robos'))->toBe(1);
});

it('os dois perfis abrem a tela de visitas com os números', function (string $perfil) {
    $this->withHeader('User-Agent', NAVEGADOR_DE_VERDADE)->get('/');
    $this->withHeader('User-Agent', NAVEGADOR_DE_VERDADE)->get('/como-trabalhamos');

    $this->actingAs(contaDeVisitas($perfil))->get('/painel/visitas')->assertOk()
        ->assertInertia(fn (AssertableInertia $p) => $p->component('Painel/Visitas')
            ->where('erro', false)
            ->where('resumo.visualizacoes', 2)
            ->where('resumo.visitantes', 1)
            ->has('porDia', 30)
            ->where('paginas.0.titulo', 'Página inicial')->where('paginas.1.titulo', 'Como trabalhamos'));
})->with([Perfis::ADMINISTRADOR, Perfis::REDACAO]);

it('o início mostra a semana para quem pode ver', function () {
    $this->actingAs(contaDeVisitas(Perfis::REDACAO))->get('/painel')->assertOk()
        ->assertInertia(fn (AssertableInertia $p) => $p->where('visitas.atual.visitantes', 0)
            ->where('auth.permissoes.visitas', true));
});

it('a tela de visitas exige login', function () {
    $this->get('/painel/visitas')->assertRedirect(route('login'));
});
