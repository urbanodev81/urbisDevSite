<?php

use App\Models\User;
use App\Support\Alertas\AlertaDeErro;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Teto de 5 tentativas e o operador avisado — regra da casa desde 29/09/2026,
 * molde do USI (2fa5546). Pendência 134: o login travava só por e-mail+IP,
 * login/esqueci/redefinir não tinham throttle de rota, e nenhum 500 nem conta
 * travada avisava ninguém.
 */
beforeEach(function () {
    $this->withoutDefer();
});

// O Girls não tem UserFactory: a conta nasce como nos outros testes de login.
function contaDoTeto(string $email): User
{
    return User::create([
        'name' => 'Teto', 'email' => $email, 'password' => 'password',
        'ativo' => true, 'email_verified_at' => now(),
    ]);
}

test('trocar de IP não contorna o bloqueio da conta', function () {
    $user = contaDoTeto('rodizio@example.com');

    foreach (range(1, 5) as $i) {
        $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$i}"])
            ->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $user->email, 'password' => 'errada']);
    }

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
        ->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('conta travada avisa o operador por e-mail — uma vez por e-mail e IP', function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    contaDoTeto('alvo@example.com');
    Mail::shouldReceive('send')->once();

    foreach (range(1, 8) as $i) {
        $this->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => 'alvo@example.com', 'password' => 'errada-'.$i]);
    }
});

test('login, esqueci-senha e redefinir aceitam no máximo 5 por minuto, cada um no seu balde', function () {
    Mail::fake();

    foreach (['/painel/login' => ['email' => 'x@example.com', 'password' => 'errada'],
        '/painel/forgot-password' => ['email' => 'x@example.com'],
        '/painel/reset-password' => ['token' => 't', 'email' => 'x@example.com', 'password' => 'a', 'password_confirmation' => 'a']] as $url => $dados) {
        foreach (range(1, 5) as $i) {
            expect($this->post($url, $dados)->status())->not->toBe(429);
        }
        $this->post($url, $dados)->assertStatus(429);
    }
});

test('o 500 avisa o operador, e o mesmo erro repetido não vira rajada', function () {
    Mail::shouldReceive('send')->twice();

    $erro = new RuntimeException('Quebrou');
    AlertaDeErro::avisar($erro);
    AlertaDeErro::avisar($erro);
    AlertaDeErro::avisar(new RuntimeException('Outro'));
});

test('esqueci-senha exige captcha', function () {
    $this->post('/painel/forgot-password', ['email' => 'x@example.com'])
        ->assertSessionHasErrors('captcha_token');

    $this->post('/painel/forgot-password', ['email' => 'x@example.com', 'captcha_token' => 'inventado'])
        ->assertSessionHasErrors('captcha_token');
});

test('chave de rota que não é UUID dá 404, não 500 (22P02)', function () {
    (new User)->resolveRouteBinding('nao-e-uuid');
})->throws(NotFoundHttpException::class);

test('e-mail sem conta e pedido repetido recebem a mesma resposta do envio (pendência 107)', function () {
    Notification::fake();
    $neutra = 'Se o e-mail estiver cadastrado, enviamos o link de redefinição.';

    $user = contaDoTeto('esqueci@example.com');

    // Envio real, e-mail inexistente (era INVALID_USER) e reenvio dentro do
    // cooldown (era RESET_THROTTLED): os três precisam ser indistinguíveis.
    foreach ([$user->email, 'ninguem@example.com', $user->email] as $email) {
        $this->post('/painel/forgot-password', ['email' => $email, 'captcha_token' => CAPTCHA_DE_TESTE])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', $neutra);
    }
});
