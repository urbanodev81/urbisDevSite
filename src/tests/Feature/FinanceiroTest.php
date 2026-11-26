<?php

declare(strict_types=1);

use App\Console\Commands\AvisarVencimentos;
use App\Models\Auditoria;
use App\Models\Compromisso;
use App\Models\SocratesOutbox;
use App\Models\User;
use App\Services\Socrates\ClienteDoSocrates;
use App\Support\Avisos\EventoDoSistema;
use App\Support\Financeiro\Dinheiro;
use App\Support\Perfis;
use Carbon\CarbonImmutable;
use Database\Seeders\PerfisESeguranca;
use Illuminate\Support\Facades\Queue;

/**
 * Financeiro (26/11/2026). O que se prova: só o administrador entra; cadastra
 * com valor em reais e grava centavos; pagar empurra o vencimento e guarda o
 * histórico; uma vez só vai para arquivado; estorno volta a data sem apagar;
 * o aviso sai uma vez por vencimento, o atrasado a cada 7 dias, e o corpo vai
 * à outbox com a severidade; arquivado não avisa; nada se apaga.
 */
beforeEach(function () {
    $this->seed(PerfisESeguranca::class);
    Queue::fake();
    // Na suíte o cliente nunca se diz configurado (não fala com a rede); aqui
    // ele finge que sim para a outbox gravar, como no AvisoAoOperadorTest.
    $client = Mockery::mock(ClienteDoSocrates::class)->makePartial();
    $client->shouldReceive('configurado')->andReturn(true);
    app()->instance(ClienteDoSocrates::class, $client);
    config(['socrates.url' => 'https://socrates.teste', 'socrates.token' => 'token-teste']);
    CarbonImmutable::setTestNow('2026-11-26 09:00:00');
    \Illuminate\Support\Carbon::setTestNow('2026-11-26 09:00:00');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
    \Illuminate\Support\Carbon::setTestNow();
});

function contaDoFinanceiro(string $perfil): User
{
    $usuario = User::create([
        'name' => 'Conta '.$perfil,
        'email' => 'financeiro-'.$perfil.'@teste.local',
        'password' => 'senha-de-teste-123',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);
    $usuario->syncRoles([$perfil]);

    return $usuario;
}

function compromisso(array $extra = []): Compromisso
{
    return Compromisso::create([
        'tipo' => 'hospedagem', 'sentido' => 'pagar', 'nome' => 'Hospedagem urbanodev.com.br',
        'contraparte' => 'MCO2', 'valor_centavos' => 48000, 'periodicidade' => 'anual',
        'proximo_vencimento' => '2026-12-05', 'avisar_dias_antes' => 15, ...$extra,
    ]);
}

it('converte o valor escrito em reais para centavos', function () {
    expect(Dinheiro::centavos('1.234,56'))->toBe(123456)
        ->and(Dinheiro::centavos('R$ 90'))->toBe(9000)
        ->and(Dinheiro::centavos('49,9'))->toBe(4990)
        ->and(Dinheiro::centavos('1234.5'))->toBe(123450)
        ->and(Dinheiro::centavos(''))->toBeNull()
        ->and(Dinheiro::centavos('abc'))->toBeFalse()
        ->and(Dinheiro::formatar(123456))->toBe('R$ 1.234,56');
});

it('só o administrador abre o Financeiro', function () {
    $this->actingAs(contaDoFinanceiro(Perfis::REDACAO))->get('/painel/financeiro')->assertForbidden();

    $this->actingAs(contaDoFinanceiro(Perfis::ADMINISTRADOR))->get('/painel/financeiro')->assertOk()
        ->assertInertia(fn ($p) => $p->component('Painel/Financeiro/Index')->has('compromissos', 0)->has('opcoes.tipos', 5));
});

it('cadastra com valor em reais, e a tela mostra situação e totais', function () {
    $admin = contaDoFinanceiro(Perfis::ADMINISTRADOR);

    $this->actingAs($admin)->post('/painel/financeiro', [
        'tipo' => 'mensalidade', 'sentido' => 'receber', 'nome' => 'Site Imóveis Jacob', 'contraparte' => 'Imóveis Jacob',
        'valor' => '149,90', 'periodicidade' => 'mensal', 'proximo_vencimento' => '2026-11-28', 'avisar_dias_antes' => 3,
    ])->assertSessionHasNoErrors();

    $c = Compromisso::sole();
    expect($c->valor_centavos)->toBe(14990)->and($c->situacao()['texto'])->toBe('Vence em 2 dias');

    $this->actingAs($admin)->get('/painel/financeiro')
        ->assertInertia(fn ($p) => $p->where('totais.receberPorMes', 'R$ 149,90')->where('compromissos.0.situacao.chave', 'perto'));

    $this->actingAs($admin)->post('/painel/financeiro', [
        'tipo' => 'outro', 'sentido' => 'pagar', 'nome' => 'X', 'valor' => 'muito', 'periodicidade' => 'mensal',
        'proximo_vencimento' => '2026-12-01', 'avisar_dias_antes' => 3,
    ])->assertSessionHasErrors('valor');

    expect(Auditoria::where('auditavel_type', Compromisso::class)->where('evento', 'criado')->count())->toBe(1);
});

it('pagar empurra o vencimento e guarda o histórico; estornar volta sem apagar', function () {
    $admin = contaDoFinanceiro(Perfis::ADMINISTRADOR);
    $c = compromisso();

    $this->actingAs($admin)->post("/painel/financeiro/{$c->id}/pagamentos", ['valor' => '480,00', 'pago_em' => '2026-11-26', 'forma' => 'pix'])
        ->assertSessionHasNoErrors();

    $c->refresh();
    expect($c->proximo_vencimento->toDateString())->toBe('2027-12-05')
        ->and($c->pagamentos)->toHaveCount(1)
        ->and($c->pagamentos->first()->referente_a->toDateString())->toBe('2026-12-05');

    // Pago no futuro não vale.
    $this->actingAs($admin)->post("/painel/financeiro/{$c->id}/pagamentos", ['pago_em' => '2026-12-30'])->assertSessionHasErrors('pago_em');

    $p = $c->pagamentos->first();
    $this->actingAs($admin)->post("/painel/financeiro/{$c->id}/pagamentos/{$p->id}/estornar")->assertSessionHasNoErrors();

    $c->refresh();
    expect($c->proximo_vencimento->toDateString())->toBe('2026-12-05')
        ->and($c->pagamentos()->count())->toBe(1)
        ->and($p->fresh()->estornado_em)->not->toBeNull();
});

it('só o último pagamento válido se estorna', function () {
    $c = compromisso(['periodicidade' => 'mensal']);
    $primeiro = $c->registrarPagamento(['valor_centavos' => 100, 'pago_em' => '2026-11-26', 'forma' => null, 'observacao' => null]);
    $c->registrarPagamento(['valor_centavos' => 100, 'pago_em' => '2026-11-26', 'forma' => null, 'observacao' => null]);

    expect($c->fresh()->estornar($primeiro))->toBeFalse()
        ->and($c->fresh()->proximo_vencimento->toDateString())->toBe('2027-02-05');
});

it('uma vez só: pagou, vai para os arquivados e para de avisar', function () {
    $c = compromisso(['tipo' => 'lembrete', 'nome' => 'Subir a vitrine na MCO2', 'valor_centavos' => null, 'periodicidade' => 'unica', 'proximo_vencimento' => '2026-11-26']);

    $c->registrarPagamento(['valor_centavos' => null, 'pago_em' => '2026-11-26', 'forma' => null, 'observacao' => null]);

    expect($c->fresh()->arquivado())->toBeTrue();
    $this->artisan('financeiro:avisar')->expectsOutputToContain('0 avisados.')->run();
});

it('avisa uma vez por vencimento, com a severidade, pela outbox', function () {
    compromisso(); // vence 05/12, janela de 15 dias: já entrou
    compromisso(['nome' => 'Domínio longe', 'proximo_vencimento' => '2027-06-01']);

    $this->artisan('financeiro:avisar --dry-run')->expectsOutputToContain('1 seriam avisados.')->run();
    expect(SocratesOutbox::count())->toBe(0);

    $this->artisan('financeiro:avisar')->expectsOutputToContain('1 avisados.')->run();
    $this->artisan('financeiro:avisar')->expectsOutputToContain('0 avisados.')->run();

    $linha = SocratesOutbox::sole();
    expect($linha->tipo)->toBe('evento.relatado')
        ->and($linha->payload['chave'])->toBe(EventoDoSistema::VencimentoProximo->value)
        ->and($linha->payload['severidade'])->toBe('normal')
        ->and($linha->payload['titulo'])->toBe('Hospedagem urbanodev.com.br — vence em 05/12')
        ->and($linha->payload['resumo'])->toContain('MCO2')->toContain('R$ 480,00');
});

it('atrasado avisa no primeiro dia e de novo a cada 7, como urgente', function () {
    $c = compromisso(['proximo_vencimento' => '2026-11-25']);
    $comando = app(AvisarVencimentos::class);
    $hoje = CarbonImmutable::today();

    expect($comando->eventoDoDia($c, $hoje))->toBe(EventoDoSistema::VencimentoAtrasado);

    $this->artisan('financeiro:avisar')->expectsOutputToContain('1 avisados.')->run();
    expect(SocratesOutbox::sole()->payload['severidade'])->toBe('urgente');

    $c->refresh();
    expect($comando->eventoDoDia($c, $hoje->addDays(6)))->toBeNull()
        ->and($comando->eventoDoDia($c, $hoje->addDays(7)))->toBe(EventoDoSistema::VencimentoAtrasado);
});

it('arquivado não avisa, e reativar volta a avisar; nada tem rota de apagar', function () {
    $admin = contaDoFinanceiro(Perfis::ADMINISTRADOR);
    $c = compromisso();

    $this->actingAs($admin)->post("/painel/financeiro/{$c->id}/arquivar")->assertSessionHasNoErrors();
    $this->artisan('financeiro:avisar')->expectsOutputToContain('0 avisados.')->run();

    $this->actingAs($admin)->post("/painel/financeiro/{$c->id}/reativar");
    $this->artisan('financeiro:avisar')->expectsOutputToContain('1 avisados.')->run();

    $this->actingAs($admin)->delete("/painel/financeiro/{$c->id}")->assertStatus(405);
    expect(Compromisso::count())->toBe(1);
});

it('o Início mostra o que vence em 30 dias só para quem gerencia', function () {
    compromisso();

    $this->actingAs(contaDoFinanceiro(Perfis::ADMINISTRADOR))->get('/painel')
        ->assertInertia(fn ($p) => $p->has('financeiro', 1)->where('financeiro.0.vencimento', '05/12'));

    $this->actingAs(contaDoFinanceiro(Perfis::REDACAO))->get('/painel')
        ->assertInertia(fn ($p) => $p->where('financeiro', null));
});
