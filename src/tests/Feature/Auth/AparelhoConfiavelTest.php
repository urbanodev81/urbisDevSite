<?php

declare(strict_types=1);

use App\Models\AparelhoConfiavel;
use App\Models\User;
use App\Services\Auth\AparelhosConfiaveis;
use App\Services\Auth\TotpService;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia;

/*
 * Aparelho confiável, com apelido (decisão do Ricardo, 10/10/2026).
 *
 * O que este arquivo segura: o cookie só dispensa o CÓDIGO, nunca a senha; ele
 * vale para a conta que confiou e para nenhuma outra; e some sozinho quando a
 * senha muda ou o segundo fator é desligado.
 */

beforeEach(fn () => $this->seed(PerfisESeguranca::class));

/** Conta com segundo fator ativo: [usuário, segredo]. Sem fábrica neste repo. */
function contaComCodigo(string $perfil = Perfis::REDACAO, string $nome = 'Conta Confiável'): array
{
    static $n = 0;
    $n++;

    $segredo = app(TotpService::class)->generateSecretKey();

    $usuario = User::create([
        'name' => "{$nome} {$n}",
        'email' => "aparelho-{$n}@login.local",
        'password' => 'password',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);
    $usuario->syncRoles([$perfil]);

    $usuario->forceFill([
        'dois_fatores_segredo' => $segredo,
        'dois_fatores_codigos_recuperacao' => [Hash::make('reserva-0001')],
        'dois_fatores_confirmado_em' => now(),
    ])->save();

    return [$usuario->fresh(), $segredo];
}

/** Entra com senha e código, confiando no aparelho; devolve o valor do cookie. */
function entrarConfiando($teste, User $usuario, string $segredo, string $apelido = 'Notebook do escritório'): string
{
    $teste->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $usuario->email, 'password' => 'password']);

    $resposta = $teste->post(route('two-factor.challenge'), [
        'code' => app(TotpService::class)->codeFor($segredo),
        'confiar' => true,
        'apelido' => $apelido,
    ]);

    $resposta->assertSessionHasNoErrors();

    $cookie = $resposta->getCookie(AparelhosConfiaveis::COOKIE, decrypt: true, unserialize: false);
    expect($cookie)->not->toBeNull();

    $teste->post('/painel/logout');
    $teste->assertGuest();

    // O valor cifrado traz o prefixo de validação do framework.
    return (string) str($cookie->getValue())->after('|');
}

test('sem marcar a opção, nenhum aparelho passa a ser confiável', function () {
    [$usuario, $segredo] = contaComCodigo();

    $this->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $usuario->email, 'password' => 'password']);
    $this->post(route('two-factor.challenge'), ['code' => app(TotpService::class)->codeFor($segredo)])
        ->assertCookieMissing(AparelhosConfiaveis::COOKIE);

    expect(AparelhoConfiavel::count())->toBe(0);
});

test('confiar pede o apelido, e sem o código certo não confia em nada', function () {
    [$usuario, $segredo] = contaComCodigo();

    $this->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $usuario->email, 'password' => 'password']);

    $this->post(route('two-factor.challenge'), [
        'code' => app(TotpService::class)->codeFor($segredo),
        'confiar' => true,
        'apelido' => '',
    ])->assertSessionHasErrors('apelido');

    $this->post(route('two-factor.challenge'), [
        'code' => '000000',
        'confiar' => true,
        'apelido' => 'Notebook',
    ])->assertSessionHasErrors('code');

    $this->assertGuest();
    expect(AparelhoConfiavel::count())->toBe(0);
});

test('no aparelho confiável o acesso seguinte pede só a senha', function () {
    [$usuario, $segredo] = contaComCodigo();
    $cookie = entrarConfiando($this, $usuario, $segredo);

    $aparelho = AparelhoConfiavel::sole();
    expect($aparelho->apelido)->toBe('Notebook do escritório')
        ->and($aparelho->user_id)->toBe($usuario->id)
        // No banco fica o hash, nunca o segredo.
        ->and($aparelho->token_hash)->toBe(hash('sha256', $cookie))
        ->and($aparelho->expira_em->isSameDay(now()->addDays(AparelhosConfiaveis::DIAS)))->toBeTrue();

    $this->withCookie(AparelhosConfiaveis::COOKIE, $cookie)
        ->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $usuario->email, 'password' => 'password'])
        ->assertRedirect(route('painel.inicio', absolute: false));

    $this->assertAuthenticatedAs($usuario);
});

test('o aparelho confiável nunca dispensa a senha', function () {
    [$usuario, $segredo] = contaComCodigo();
    $cookie = entrarConfiando($this, $usuario, $segredo);

    $this->withCookie(AparelhosConfiaveis::COOKIE, $cookie)
        ->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $usuario->email, 'password' => 'errada'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('o cookie de uma conta não dispensa o código de outra', function () {
    [$usuario, $segredo] = contaComCodigo();
    [$outra] = contaComCodigo();
    $cookie = entrarConfiando($this, $usuario, $segredo);

    $this->withCookie(AparelhosConfiaveis::COOKIE, $cookie)
        ->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $outra->email, 'password' => 'password'])
        ->assertRedirect(route('two-factor.challenge'));

    $this->assertGuest();
});

test('aparelho vencido e cookie inventado voltam a pedir o código', function () {
    [$usuario, $segredo] = contaComCodigo();
    $cookie = entrarConfiando($this, $usuario, $segredo);

    $this->withCookie(AparelhosConfiaveis::COOKIE, str_repeat('a', 64))
        ->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $usuario->email, 'password' => 'password'])
        ->assertRedirect(route('two-factor.challenge'));
    $this->assertGuest();

    $this->travel(AparelhosConfiaveis::DIAS + 1)->days();

    $this->withCookie(AparelhosConfiaveis::COOKIE, $cookie)
        ->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $usuario->email, 'password' => 'password'])
        ->assertRedirect(route('two-factor.challenge'));
    $this->assertGuest();
});

test('trocar a senha remove os aparelhos confiáveis', function () {
    [$usuario, $segredo] = contaComCodigo();
    $cookie = entrarConfiando($this, $usuario, $segredo);

    $this->actingAs($usuario)->put(route('password.update'), [
        'current_password' => 'password',
        'password' => 'Outra-Senha-Forte-2026!',
        'password_confirmation' => 'Outra-Senha-Forte-2026!',
    ])->assertSessionHasNoErrors();

    expect(AparelhoConfiavel::count())->toBe(0);

    $this->post('/painel/logout');

    $this->withCookie(AparelhosConfiaveis::COOKIE, $cookie)
        ->post('/painel/login', ['captcha_token' => CAPTCHA_DE_TESTE, 'email' => $usuario->email, 'password' => 'Outra-Senha-Forte-2026!'])
        ->assertRedirect(route('two-factor.challenge'));
});

test('desligar o segundo fator remove os aparelhos confiáveis', function () {
    [$usuario, $segredo] = contaComCodigo();
    entrarConfiando($this, $usuario, $segredo);

    $this->actingAs($usuario)->delete(route('two-factor.disable'), ['current_password' => 'password'])
        ->assertSessionHasNoErrors();

    expect(AparelhoConfiavel::count())->toBe(0);
});

test('Minha conta lista os aparelhos pelo apelido e marca o atual', function () {
    [$usuario, $segredo] = contaComCodigo();
    $cookie = entrarConfiando($this, $usuario, $segredo, 'Celular da Marta');

    $this->withoutVite()->actingAs($usuario)
        ->withCookie(AparelhosConfiaveis::COOKIE, $cookie)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $pagina) => $pagina
            ->has('aparelhosConfiaveis', 1)
            ->where('aparelhosConfiaveis.0.apelido', 'Celular da Marta')
            ->where('aparelhosConfiaveis.0.atual', true)
            ->missing('aparelhosConfiaveis.0.token_hash'));
});

test('a pessoa remove o próprio aparelho, e não o de outra conta', function () {
    [$usuario, $segredo] = contaComCodigo();
    [$outra, $segredoDaOutra] = contaComCodigo();
    entrarConfiando($this, $usuario, $segredo);
    entrarConfiando($this, $outra, $segredoDaOutra);

    $meu = AparelhoConfiavel::where('user_id', $usuario->id)->sole();
    $dela = AparelhoConfiavel::where('user_id', $outra->id)->sole();

    $this->actingAs($usuario)->delete(route('two-factor.aparelhos.destroy', $dela->uuid));
    expect(AparelhoConfiavel::find($dela->id))->not->toBeNull();

    $this->actingAs($usuario)->delete(route('two-factor.aparelhos.destroy', $meu->uuid))
        ->assertSessionHas('sucesso');
    expect(AparelhoConfiavel::find($meu->id))->toBeNull();
});

test('remover todos derruba os da conta e esquece o cookie deste navegador', function () {
    [$usuario, $segredo] = contaComCodigo();
    [$outra, $segredoDaOutra] = contaComCodigo();
    entrarConfiando($this, $usuario, $segredo, 'Um');
    entrarConfiando($this, $usuario, $segredo, 'Dois');
    entrarConfiando($this, $outra, $segredoDaOutra);

    $this->actingAs($usuario)->delete(route('two-factor.aparelhos.destroy-all'))
        ->assertCookieExpired(AparelhosConfiaveis::COOKIE);

    expect(AparelhoConfiavel::where('user_id', $usuario->id)->count())->toBe(0)
        ->and(AparelhoConfiavel::where('user_id', $outra->id)->count())->toBe(1);
});

test('quem tem o segundo fator desligado por outra pessoa é avisado por e-mail e perde os aparelhos', function () {
    [$admin] = contaComCodigo(Perfis::ADMINISTRADOR, 'Rui Gestor');
    [$alvo, $segredo] = contaComCodigo();
    entrarConfiando($this, $alvo, $segredo);

    Mail::mailer()->getSymfonyTransport()->flush();

    $this->actingAs($admin)
        ->delete(route('painel.usuarios.dois-fatores.desligar', $alvo), ['current_password' => 'password'])
        ->assertSessionHasNoErrors();

    $enviados = Mail::mailer()->getSymfonyTransport()->messages();
    $paraOAlvo = $enviados->filter(fn ($m) => str_contains($m->getOriginalMessage()->getSubject(), 'verificação em duas etapas'));

    expect($paraOAlvo)->toHaveCount(1);

    $mensagem = $paraOAlvo->first()->getOriginalMessage();
    expect($mensagem->getTo()[0]->getAddress())->toBe($alvo->email)
        ->and($mensagem->getHtmlBody())->toContain('Rui Gestor')
        // Diz o fato e quem fez; nunca o segredo.
        ->and($mensagem->getHtmlBody())->not->toContain($segredo);

    expect(AparelhoConfiavel::where('user_id', $alvo->id)->count())->toBe(0);
});

test('desligar o de quem nem tinha ligado não manda e-mail', function () {
    [$admin] = contaComCodigo(Perfis::ADMINISTRADOR);
    $alvo = User::create([
        'name' => 'Sem Código',
        'email' => 'sem-codigo@login.local',
        'password' => 'password',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);
    $alvo->syncRoles([Perfis::REDACAO]);

    Mail::mailer()->getSymfonyTransport()->flush();

    $this->actingAs($admin)
        ->delete(route('painel.usuarios.dois-fatores.desligar', $alvo), ['current_password' => 'password']);

    expect(Mail::mailer()->getSymfonyTransport()->messages())->toHaveCount(0);
});

test('a descrição do aparelho sai de lista fechada, não do texto do navegador', function () {
    expect(AparelhosConfiaveis::descrever('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36'))
        ->toBe('Chrome no Windows')
        ->and(AparelhosConfiaveis::descrever('Mozilla/5.0 (iPhone; CPU iPhone OS 19_0 like Mac OS X) AppleWebKit/605.1.15 Version/19.0 Mobile/15E148 Safari/604.1'))
        ->toBe('Safari no iPhone')
        ->and(AparelhosConfiaveis::descrever('<script>alert(1)</script>'))->toBe('Navegador')
        ->and(AparelhosConfiaveis::descrever(null))->toBe('Navegador');
});
