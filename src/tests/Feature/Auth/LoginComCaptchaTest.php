<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;

/**
 * O captcha no login do painel (no Duo desde 20/09/2026; aqui desde 03/11/2026, o Altcha do site).
 *
 * Até esta data este login era a porta mais aberta do repo: sem captcha, sem
 * honeypot, só o limite por tentativas — e limite segura repetição, não a
 * primeira tentativa de uma lista de credenciais vazada.
 *
 * Sem `User::factory()` de propósito: as contas deste painel nascem com perfil
 * e `ativo`, como faz `LoginLevaAoPainelTest`. Uma conta sem isso passa pelo
 * login e quebra depois, por outro motivo.
 */
beforeEach(fn () => $this->seed(PerfisESeguranca::class));

function contaParaCaptcha(): User
{
    $usuario = User::create([
        'name' => 'Conta do captcha',
        'email' => 'captcha@login.local',
        'password' => 'senha-de-teste-123',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);

    $usuario->syncRoles([Perfis::ADMINISTRADOR]);

    return $usuario;
}

test('sem captcha o login não entra, nem com a senha certa', function () {
    $usuario = contaParaCaptcha();

    $this->post(route('login'), ['email' => $usuario->email, 'password' => 'senha-de-teste-123'])
        ->assertSessionHasErrors('captcha_token');

    $this->post(route('login'), ['email' => $usuario->email, 'password' => 'senha-de-teste-123', 'captcha_token' => 'inventado'])
        ->assertSessionHasErrors('captcha_token');

    $this->assertGuest();
});

test('com captcha válido o login entra', function () {
    $usuario = contaParaCaptcha();

    $this->post(route('login'), ['email' => $usuario->email, 'password' => 'senha-de-teste-123', 'captcha_token' => CAPTCHA_DE_TESTE])
        ->assertSessionDoesntHaveErrors('captcha_token');

    $this->assertAuthenticated();
});

// O atalho do smoke de login pós-deploy (CAPTCHA_QA_BYPASS_TOKEN).
test('o atalho de QA vale fora de produção', function () {
    config(['services.captcha.qa_bypass_token' => 'atalho-qa']);
    $usuario = contaParaCaptcha();

    $this->post(route('login'), ['email' => $usuario->email, 'password' => 'senha-de-teste-123', 'captcha_token' => 'atalho-qa'])
        ->assertSessionDoesntHaveErrors('captcha_token');

    $this->assertAuthenticated();
});

test('o atalho de QA é ignorado em produção', function () {
    config(['services.captcha.qa_bypass_token' => 'atalho-qa']);
    $usuario = contaParaCaptcha();
    $this->app['env'] = 'production';

    try {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class)
            ->post(route('login'), ['email' => $usuario->email, 'password' => 'senha-de-teste-123', 'captcha_token' => 'atalho-qa'])
            ->assertSessionHasErrors('captcha_token');
    } finally {
        $this->app['env'] = 'testing';
    }

    $this->assertGuest();
});
