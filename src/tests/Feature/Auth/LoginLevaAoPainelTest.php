<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;

/**
 * O POST de login precisa TERMINAR bem, não só autenticar.
 *
 * Este arquivo existe por um defeito real, achado rodando o app em 31/08/2026:
 * o controller herdado do irmão redirecionava para `route('dashboard')` /
 * `route('portal.inicio')`, que não existem aqui. O resultado era 500 **com a
 * sessão já criada** — a pessoa entrava e via uma tela de erro, e a próxima
 * navegação funcionava, o que torna o defeito confuso de relatar.
 *
 * `actingAs()` não pega isso: ele injeta o usuário e pula o controller. Por
 * isso o teste faz o POST de verdade.
 */
beforeEach(fn () => $this->seed(PerfisESeguranca::class));

function contaParaLogin(string $perfil): User
{
    $usuario = User::create([
        'name' => 'Login '.$perfil,
        'email' => $perfil.'@login.local',
        'password' => 'senha-de-teste-123',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);

    $usuario->syncRoles([$perfil]);

    return $usuario;
}

it('leva ao painel depois de entrar', function (string $perfil) {
    $usuario = contaParaLogin($perfil);

    $this->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $usuario->email, 'password' => 'senha-de-teste-123'])
        ->assertRedirect(route('painel.inicio', absolute: false));

    $this->assertAuthenticatedAs($usuario);
})->with([Perfis::ADMINISTRADOR, Perfis::REDACAO, Perfis::REDACAO]);

it('devolve para onde a pessoa queria ir', function () {
    $usuario = contaParaLogin(Perfis::ADMINISTRADOR);

    // Quem clicou num link do painel e caiu no login volta para o link.
    $this->get('/painel/usuarios')->assertRedirect(route('login'));

    $this->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $usuario->email, 'password' => 'senha-de-teste-123'])
        ->assertRedirect('/painel/usuarios');
});

it('carimba o último acesso', function () {
    $usuario = contaParaLogin(Perfis::REDACAO);

    expect($usuario->ultimo_acesso_em)->toBeNull();

    $this->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $usuario->email, 'password' => 'senha-de-teste-123']);

    expect($usuario->fresh()->ultimo_acesso_em)->not->toBeNull();
});

it('conta desativada não entra, nem com a senha certa', function () {
    $usuario = contaParaLogin(Perfis::REDACAO);
    $usuario->update(['ativo' => false]);

    $this->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $usuario->email, 'password' => 'senha-de-teste-123'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});
