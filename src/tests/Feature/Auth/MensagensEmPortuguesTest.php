<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;

/**
 * As mensagens do painel em português (08/11/2026).
 *
 * O `locale` sempre foi `pt_BR`, mas o repo não tinha `lang/`: sem tradução, o
 * Laravel caía no inglês do fallback e o login respondia "These credentials do
 * not match our records". A pasta veio do molde (Duo/WJ, que já a tinham), com
 * os nomes de campo trocados pelos daqui.
 */
beforeEach(fn () => $this->seed(PerfisESeguranca::class));

test('senha errada responde em português', function () {
    $usuario = User::create([
        'name' => 'Conta do idioma',
        'email' => 'idioma@login.local',
        'password' => 'senha-de-teste-123',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);
    $usuario->syncRoles([Perfis::ADMINISTRADOR]);

    $this->post(route('login'), ['email' => $usuario->email, 'password' => 'senha-errada', 'captcha_token' => CAPTCHA_DE_TESTE])
        ->assertSessionHasErrors(['email' => 'Essas credenciais não correspondem aos nossos registros.']);

    $this->assertGuest();
});

test('campo obrigatório sai em português e com o nome do campo traduzido', function () {
    $this->post(route('login'), ['email' => '', 'password' => '', 'captcha_token' => CAPTCHA_DE_TESTE])
        ->assertSessionHasErrors([
            'email' => 'O campo e-mail é obrigatório.',
            'password' => 'O campo senha é obrigatório.',
        ]);
});
