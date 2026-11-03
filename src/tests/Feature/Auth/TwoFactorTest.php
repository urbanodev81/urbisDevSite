<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\TwoFactor\StoreTwoFactorChallengeController;
use App\Models\User;
use App\Services\Auth\TotpService;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;
use Illuminate\Auth\Events\Login;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

/*
 * Segundo fator (pendência 136). Molde do Sócrates, pelo desenho do BHD:
 * aplicativo autenticador mais códigos de recuperação de uso único.
 */

beforeEach(fn () => $this->seed(PerfisESeguranca::class));

/** Não há fábrica de usuário neste repo: a conta nasce como no painel. */
function contaParaSegundoFator(): User
{
    static $n = 0;
    $n++;

    $usuario = User::create([
        'name' => 'Duas Etapas '.$n,
        'email' => "duas-etapas-{$n}@login.local",
        'password' => 'password',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);

    $usuario->syncRoles([Perfis::REDACAO]);

    return $usuario;
}

/** Ativa o segundo fator já confirmado: [usuário, segredo, códigos em texto puro]. */
function comDoisFatores(int $quantosCodigos = 2): array
{
    $segredo = app(TotpService::class)->generateSecretKey();
    $codigos = collect(range(1, $quantosCodigos))->map(fn ($i) => "cod{$i}-abcd")->all();

    $usuario = contaParaSegundoFator();
    $usuario->forceFill([
        'dois_fatores_segredo' => $segredo,
        'dois_fatores_codigos_recuperacao' => array_map(fn ($c) => Hash::make($c), $codigos),
        'dois_fatores_confirmado_em' => now(),
    ])->save();

    return [$usuario->fresh(), $segredo, $codigos];
}

function codigoDe(string $segredo): string
{
    return app(TotpService::class)->codeFor($segredo);
}

test('conta sem segundo fator continua entrando só com e-mail e senha', function () {
    $usuario = contaParaSegundoFator();

    $this->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $usuario->email, 'password' => 'password'])
        ->assertRedirect(route('painel.inicio', absolute: false));

    $this->assertAuthenticatedAs($usuario);
});

test('senha certa com segundo fator ativo não autentica: manda para o desafio', function () {
    Event::fake([Login::class]);
    [$usuario] = comDoisFatores();

    $this->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $usuario->email, 'password' => 'password'])
        ->assertRedirect(route('two-factor.challenge'));

    // O ponto central: entre a senha e o código não existe sessão autenticada,
    // e nem a linha de acesso do Sócrates nem o carimbo de último acesso
    // (evento Login) saíram.
    $this->assertGuest();
    expect(session('2fa.user_id'))->toBe($usuario->id);
    Event::assertNotDispatched(Login::class);
});

test('senha errada em conta com segundo fator não abre o desafio', function () {
    [$usuario] = comDoisFatores();

    $this->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $usuario->email, 'password' => 'errada'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect(session('2fa.user_id'))->toBeNull();
});

test('a tela do desafio abre para quem passou pela senha', function () {
    $this->withoutVite();
    [$usuario] = comDoisFatores();

    $this->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $usuario->email, 'password' => 'password']);

    $this->get(route('two-factor.challenge'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $pagina) => $pagina->component('Auth/TwoFactorChallenge'));
});

test('código certo do aplicativo conclui o login', function () {
    Event::fake([Login::class]);
    [$usuario, $segredo] = comDoisFatores();

    $this->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $usuario->email, 'password' => 'password']);

    $this->post(route('two-factor.challenge'), ['code' => codigoDe($segredo)])
        ->assertRedirect(route('painel.inicio', absolute: false));

    $this->assertAuthenticatedAs($usuario);
    expect(session('2fa.user_id'))->toBeNull();
    Event::assertDispatched(Login::class);
});

test('código errado não autentica e mantém o desafio de pé', function () {
    [$usuario] = comDoisFatores();

    $this->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $usuario->email, 'password' => 'password']);

    $this->post(route('two-factor.challenge'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    $this->assertGuest();
    expect(session('2fa.user_id'))->toBe($usuario->id);
});

test('desafio sem ter passado pela senha não expõe tela nem autentica', function () {
    $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));

    $this->post(route('two-factor.challenge'), ['code' => '000000'])->assertStatus(419);

    $this->assertGuest();
});

test('código de recuperação vale uma vez só', function () {
    [$usuario, , $codigos] = comDoisFatores();

    $this->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $usuario->email, 'password' => 'password']);
    $this->post(route('two-factor.challenge'), ['code' => $codigos[0]]);
    $this->assertAuthenticatedAs($usuario);

    expect($usuario->fresh()->dois_fatores_codigos_recuperacao)->toHaveCount(1);

    // Mesmo código, segunda vez: não entra.
    $this->post('/painel/logout');
    $this->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $usuario->email, 'password' => 'password']);
    $this->post(route('two-factor.challenge'), ['code' => $codigos[0]])
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});

/**
 * O `throttle` da rota é por IP. Sem o teto por conta, quem tem a senha e
 * troca de endereço tenta o código quantas vezes quiser.
 */
test('cinco códigos errados derrubam o desafio, e o sexto não entra nem com o código certo', function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    [$usuario, $segredo] = comDoisFatores();

    $this->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $usuario->email, 'password' => 'password']);

    foreach (range(1, StoreTwoFactorChallengeController::MAX_ERROS) as $tentativa) {
        $this->post(route('two-factor.challenge'), ['code' => '000000'])
            ->assertSessionHasErrors('code');
    }

    $this->post(route('two-factor.challenge'), ['code' => codigoDe($segredo)])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect(session('2fa.user_id'))->toBeNull();
});

test('ativação só vale depois de confirmar com um código de verdade', function () {
    $usuario = contaParaSegundoFator();

    $this->actingAs($usuario)->post(route('two-factor.enable'));

    $usuario->refresh();
    expect($usuario->dois_fatores_segredo)->not->toBeNull();
    // Segredo gerado ainda NÃO liga nada — senão dá para se trancar fora da
    // conta com um QR code que o aplicativo nunca leu.
    expect($usuario->doisFatoresAtivo())->toBeFalse();

    $this->actingAs($usuario)->post(route('two-factor.confirm'), ['code' => '000000'])
        ->assertSessionHasErrors('code');
    expect($usuario->fresh()->doisFatoresAtivo())->toBeFalse();

    $this->actingAs($usuario)->post(route('two-factor.confirm'), ['code' => codigoDe($usuario->dois_fatores_segredo)])
        ->assertSessionHas('two_factor_recovery_codes');

    expect($usuario->fresh()->doisFatoresAtivo())->toBeTrue();
    expect($usuario->fresh()->dois_fatores_codigos_recuperacao)->toHaveCount(8);
});

test('os códigos de recuperação entregues na ativação abrem o login', function () {
    $usuario = contaParaSegundoFator();

    $this->actingAs($usuario)->post(route('two-factor.enable'));
    $this->actingAs($usuario)->post(route('two-factor.confirm'), [
        'code' => codigoDe($usuario->fresh()->dois_fatores_segredo),
    ]);
    $codigos = session('two_factor_recovery_codes');

    $this->post('/painel/logout');
    $this->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $usuario->email, 'password' => 'password']);
    // Digitado em maiúsculas, como sai de um papel: vale do mesmo jeito.
    $this->post(route('two-factor.challenge'), ['code' => mb_strtoupper($codigos[0])]);

    $this->assertAuthenticatedAs($usuario);
});

test('ativar de novo com o segundo fator já ligado não troca o segredo', function () {
    [$usuario, $segredo] = comDoisFatores();

    $this->actingAs($usuario)->post(route('two-factor.enable'));

    expect($usuario->fresh()->dois_fatores_segredo)->toBe($segredo);
    expect($usuario->fresh()->doisFatoresAtivo())->toBeTrue();
});

test('desativar exige a senha atual', function () {
    [$usuario] = comDoisFatores();

    $this->actingAs($usuario)->delete(route('two-factor.disable'), ['current_password' => 'errada'])
        ->assertSessionHasErrors('current_password');
    expect($usuario->fresh()->doisFatoresAtivo())->toBeTrue();

    $this->actingAs($usuario)->delete(route('two-factor.disable'), ['current_password' => 'password']);
    expect($usuario->fresh()->doisFatoresAtivo())->toBeFalse();
    expect($usuario->fresh()->dois_fatores_segredo)->toBeNull();
    expect($usuario->fresh()->dois_fatores_codigos_recuperacao)->toBeNull();
});

test('ligar, confirmar e desligar pedem login', function () {
    $this->post(route('two-factor.enable'))->assertRedirect(route('login'));
    $this->post(route('two-factor.confirm'), ['code' => '000000'])->assertRedirect(route('login'));
    $this->delete(route('two-factor.disable'))->assertRedirect(route('login'));
});

test('segredo e códigos de recuperação ficam cifrados no banco', function () {
    [$usuario, $segredo, $codigos] = comDoisFatores();

    $bruto = DB::table('users')->where('id', $usuario->id)->first();

    expect($bruto->dois_fatores_segredo)->not->toContain($segredo);
    expect($bruto->dois_fatores_codigos_recuperacao)->not->toContain($codigos[0]);
    expect($usuario->fresh()->dois_fatores_segredo)->toBe($segredo);
});

/**
 * Piso 3 do PADROES: o cast `encrypted` decripta ao acessar, e uma prop de
 * Inertia acaba no HTML da página. A chave só pode aparecer enquanto a pessoa
 * está lendo o QR code.
 */
test('Minha conta mostra a chave só durante a ativação, nunca depois', function () {
    $usuario = contaParaSegundoFator();
    $this->actingAs($usuario)->post(route('two-factor.enable'));
    $segredo = $usuario->fresh()->dois_fatores_segredo;

    $this->actingAs($usuario)->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $pagina) => $pagina
            ->where('twoFactorEnabled', false)
            ->where('twoFactorPending.secret', $segredo)
            ->missing('auth.user.dois_fatores_segredo'));

    $this->actingAs($usuario)->post(route('two-factor.confirm'), ['code' => codigoDe($segredo)]);

    $resposta = $this->actingAs($usuario->fresh())->get(route('profile.edit'));

    $resposta->assertOk()->assertInertia(fn (AssertableInertia $pagina) => $pagina
        ->where('twoFactorEnabled', true)
        ->where('twoFactorPending', null)
        ->where('twoFactorRecoveryCodesLeft', 8)
        ->missing('auth.user.dois_fatores_segredo')
        ->missing('auth.user.dois_fatores_codigos_recuperacao'));

    expect($resposta->getContent())->not->toContain($segredo);
});
