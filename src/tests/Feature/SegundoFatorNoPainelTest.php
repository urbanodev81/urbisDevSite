<?php

declare(strict_types=1);

use App\Models\Auditoria;
use App\Models\User;
use App\Services\Auth\TotpService;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;
use Illuminate\Support\Facades\Hash;

/*
 * As duas regras que o segundo fator ganhou no painel em 10/10/2026:
 * é OBRIGATÓRIO para o perfil administrador, e quem gerencia contas desliga o
 * de outra pessoa — a saída de quem perdeu o celular e os códigos de
 * recuperação.
 *
 * A suíte roda com a obrigatoriedade desligada (`phpunit.xml`); os casos
 * daqui a religam.
 */
beforeEach(function () {
    $this->seed(PerfisESeguranca::class);

    $this->admin = contaDoPainelComPerfil(Perfis::ADMINISTRADOR);
});

function contaDoPainelComPerfil(string $perfil): User
{
    static $n = 0;
    $n++;

    $usuario = User::create([
        'name' => "Conta {$perfil} {$n}",
        'email' => "{$perfil}-{$n}@painel.local",
        'password' => 'password',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);

    $usuario->syncRoles([$perfil]);

    return $usuario;
}

function ligarSegundoFatorDe(User $usuario): User
{
    $usuario->forceFill([
        'dois_fatores_segredo' => app(TotpService::class)->generateSecretKey(),
        'dois_fatores_codigos_recuperacao' => [Hash::make('cod1-abcd')],
        'dois_fatores_confirmado_em' => now(),
    ])->save();

    return $usuario->fresh();
}

/*
|--------------------------------------------------------------------------
| Obrigatório para o administrador
|--------------------------------------------------------------------------
*/

test('administrador que não ativou é levado para Minha conta', function () {
    config(['auth.dois_fatores_obrigatorio' => true]);

    $this->actingAs($this->admin)->get(route('painel.inicio'))
        ->assertRedirect(route('profile.edit'));
    $this->actingAs($this->admin)->get(route('painel.usuarios.index'))
        ->assertRedirect(route('profile.edit'));
    // Escrita também: o que fecha é o painel, não só as telas.
    $this->actingAs($this->admin)->post(route('painel.usuarios.store'), [])
        ->assertRedirect(route('profile.edit'));
});

test('a tela que resolve, a ativação e o sair continuam abertos', function () {
    config(['auth.dois_fatores_obrigatorio' => true]);

    $this->actingAs($this->admin)->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina->where('twoFactorRequired', true));

    $this->actingAs($this->admin)->post(route('two-factor.enable'))->assertSessionHasNoErrors();
    $segredo = $this->admin->fresh()->dois_fatores_segredo;
    expect($segredo)->not->toBeNull();

    $this->actingAs($this->admin)->post(route('two-factor.confirm'), [
        'code' => app(TotpService::class)->codeFor($segredo),
    ])->assertSessionHas('two_factor_recovery_codes');

    // Ativou: o painel abre.
    $this->actingAs($this->admin->fresh())->get(route('painel.inicio'))->assertOk();

    $this->actingAs($this->admin->fresh())->post(route('logout'))->assertRedirect('/');
});

// Diferente do molde (lá só o administrador): aqui TODA conta é obrigada,
// decisão de 03/11/2026 — o painel fica na internet sem muro nem lista de IP.
test('a redação também é obrigada ao segundo fator', function () {
    config(['auth.dois_fatores_obrigatorio' => true]);

    $conta = contaDoPainelComPerfil(Perfis::REDACAO);

    expect($conta->exigeDoisFatores())->toBeTrue();

    $this->actingAs($conta)->get(route('painel.inicio'))->assertRedirect(route('profile.edit'));
});

test('o que é público não fecha para o administrador que deve o segundo fator', function () {
    config(['auth.dois_fatores_obrigatorio' => true]);

    // O site público continua abrindo para quem está logado sem o 2º fator.
    $this->actingAs($this->admin)->get('/como-trabalhamos')->assertOk();
});

test('com a chave desligada ninguém é obrigado', function () {
    config(['auth.dois_fatores_obrigatorio' => false]);

    $this->actingAs($this->admin)->get(route('painel.inicio'))->assertOk();
});

/*
|--------------------------------------------------------------------------
| Desligar o de outra pessoa
|--------------------------------------------------------------------------
*/

test('quem gerencia contas desliga o segundo fator de outra, com a própria senha', function () {
    $alvo = ligarSegundoFatorDe(contaDoPainelComPerfil(Perfis::REDACAO));

    $this->actingAs($this->admin)
        ->delete(route('painel.usuarios.dois-fatores.desligar', $alvo), ['current_password' => 'errada'])
        ->assertSessionHasErrors('current_password');
    expect($alvo->fresh()->doisFatoresAtivo())->toBeTrue();

    $this->actingAs($this->admin)
        ->delete(route('painel.usuarios.dois-fatores.desligar', $alvo), ['current_password' => 'password'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('sucesso');

    $alvo->refresh();
    expect($alvo->doisFatoresAtivo())->toBeFalse();
    expect($alvo->dois_fatores_segredo)->toBeNull();
    expect($alvo->dois_fatores_codigos_recuperacao)->toBeNull();
});

test('desligar o de outra conta fica na trilha, sem o segredo', function () {
    $alvo = ligarSegundoFatorDe(contaDoPainelComPerfil(Perfis::REDACAO));
    $segredo = $alvo->dois_fatores_segredo;

    $this->actingAs($this->admin)
        ->delete(route('painel.usuarios.dois-fatores.desligar', $alvo), ['current_password' => 'password']);

    // Só as linhas de quem desligou: ligar, no preparo do teste, também
    // deixa linha, e essa não tem autor.
    $linhas = Auditoria::query()
        ->where('auditavel_type', $alvo->getMorphClass())
        ->where('auditavel_id', $alvo->id)
        ->where('user_id', $this->admin->id)
        ->get();

    expect($linhas->pluck('campo')->all())->toContain('dois_fatores_confirmado_em');
    expect($linhas->toJson())->not->toContain($segredo);
});

test('a própria conta não se desliga por aqui', function () {
    $admin = ligarSegundoFatorDe($this->admin);

    $this->actingAs($admin)
        ->delete(route('painel.usuarios.dois-fatores.desligar', $admin), ['current_password' => 'password'])
        ->assertForbidden();

    expect($admin->fresh()->doisFatoresAtivo())->toBeTrue();
});

test('quem não gerencia contas não desliga o de ninguém', function () {
    $alvo = ligarSegundoFatorDe(contaDoPainelComPerfil(Perfis::REDACAO));
    $daBanda = contaDoPainelComPerfil(Perfis::REDACAO);

    $this->actingAs($daBanda)
        ->delete(route('painel.usuarios.dois-fatores.desligar', $alvo), ['current_password' => 'password'])
        ->assertForbidden();

    expect($alvo->fresh()->doisFatoresAtivo())->toBeTrue();
});

test('a tela de edição diz se a conta tem segundo fator, e nunca a chave', function () {
    $alvo = ligarSegundoFatorDe(contaDoPainelComPerfil(Perfis::REDACAO));

    $resposta = $this->actingAs($this->admin)->get(route('painel.usuarios.edit', $alvo));

    $resposta->assertOk()->assertInertia(fn ($pagina) => $pagina
        ->where('usuario.doisFatoresAtivo', true)
        ->where('usuario.euMesmo', false)
        ->missing('usuario.dois_fatores_segredo'));

    expect($resposta->getContent())->not->toContain($alvo->dois_fatores_segredo);
});
