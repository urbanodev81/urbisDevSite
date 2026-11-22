<?php

declare(strict_types=1);

use App\Models\SocratesOutbox;
use App\Models\User;
use App\Services\Auth\TotpService;
use App\Services\Socrates\ClienteDoSocrates;
use App\Services\Socrates\OutboxDoSocrates;
use App\Support\Alertas\AlertaDeErro;
use App\Support\Avisos\AvisoAoOperador;
use App\Support\Avisos\EventoDoSistema;
use App\Support\Avisos\IpsDeAcesso;
use App\Support\Avisos\SinaisDoLogin;
use App\Support\Avisos\SinalDeAtaque;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Client\Request as RequisicaoHttp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/*
 * Pendência 284 F3 (ADR 0006 do Sócrates): o que o urbisdev conta ao
 * OPERADOR pelo Sócrates, como origem `nosso`. Molde: WJ (F2).
 *
 * O client é blindado em `testing`; aqui ele é um mock parcial que diz
 * "configurado" — `detectEnvironment('local')` religaria o CSRF e todo POST
 * de teste viraria 419 (achado da F2). A fila vai falsa para a linha ficar na
 * outbox e o payload poder ser lido.
 */
function socratesLigadoNoSite(): void
{
    $client = Mockery::mock(ClienteDoSocrates::class)->makePartial();
    $client->shouldReceive('configurado')->andReturn(true);
    app()->instance(ClienteDoSocrates::class, $client);

    config(['socrates.url' => 'http://socrates.test', 'socrates.token' => 'token-de-teste']);
}

/** @return list<array<string, mixed>> */
function avisosNaOutbox(?string $chave = null): array
{
    return SocratesOutbox::query()
        ->where('tipo', OutboxDoSocrates::EVENTO_RELATADO)
        ->get()
        ->pluck('payload')
        ->filter(fn (array $p) => $chave === null || $p['chave'] === $chave)
        ->values()
        ->all();
}

function contaDoSiteParaAvisos(string $perfil, string $email): User
{
    $u = User::create([
        'name' => "Conta {$perfil}", 'email' => $email, 'password' => 'senha-de-teste-123',
        'ativo' => true, 'email_verified_at' => now(),
    ]);
    $u->syncRoles([$perfil]);

    return $u;
}

beforeEach(function () {
    Queue::fake();
    Mail::fake();
});

test('sem Sócrates configurado nada é gravado, e ninguém estoura', function () {
    AvisoAoOperador::relatar(EventoDoSistema::Erro500, 'Erro 500: teste');

    expect(SocratesOutbox::count())->toBe(0);
});

test('o 500 vai ao Sócrates uma vez por erro distinto, como nosso, sem e-mail nem telefone', function () {
    socratesLigadoNoSite();
    $erro = new RuntimeException('Falhou ao gravar joana@exemplo.test fone 21946324461');

    AlertaDeErro::avisar($erro);
    AlertaDeErro::avisar($erro);

    $eventos = avisosNaOutbox('erro.500');

    expect($eventos)->toHaveCount(1)
        ->and($eventos[0]['origem'])->toBe('nosso')
        ->and($eventos[0]['titulo'])->toStartWith('Erro 500: ')
        ->and($eventos[0]['titulo'])->not->toContain('joana@')
        ->and($eventos[0]['titulo'])->not->toContain('21946324461')
        ->and($eventos[0]['resumo'])->toContain('RuntimeException');
});

test('a outbox entrega em /api/eventos com a chave de idempotência', function () {
    socratesLigadoNoSite();
    Http::fake(['socrates.test/*' => Http::response(['ok' => true], 201)]);

    AvisoAoOperador::relatar(EventoDoSistema::RajadaNoLogin, 'Rajada', 'vinte tentativas');
    app(OutboxDoSocrates::class)->entregarPendentes();

    Http::assertSent(fn (RequisicaoHttp $r) => $r->url() === 'http://socrates.test/api/eventos'
        && Str::isUuid($r->header('Idempotency-Key')[0])
        && $r['chave'] === 'seguranca.rajada_login'
        && $r['origem'] === 'nosso'
        && filled($r['ocorreu_em']));
    expect(SocratesOutbox::count())->toBe(0);
});

test('o aviso não depende do SOCRATES_LEADS, que só vale para o lead', function () {
    socratesLigadoNoSite();
    config(['socrates.leads' => false]);

    AvisoAoOperador::relatar(EventoDoSistema::Erro500, 'Erro 500: x');

    expect(avisosNaOutbox('erro.500'))->toHaveCount(1);
});

test('conta travada vai como seguranca.conta_travada, com a conta mascarada', function () {
    socratesLigadoNoSite();
    $request = Request::create('/painel/login', 'POST', ['email' => 'ricardo@urbisdev.tech'], server: ['REMOTE_ADDR' => '203.0.113.7']);

    event(new Lockout($request));

    $eventos = avisosNaOutbox('seguranca.conta_travada');
    expect($eventos)->toHaveCount(1)
        ->and($eventos[0]['resumo'])->toContain('r***@urbisdev.tech')
        ->and($eventos[0]['resumo'])->toContain('203.0.113.7')
        ->and($eventos[0]['resumo'])->not->toContain('ricardo@');
});

test('rajada no login: só acusa com o limite E três IPs, e uma vez por janela', function () {
    socratesLigadoNoSite();
    $falha = fn (string $ip) => SinaisDoLogin::falhaDeSenha(Request::create('/painel/login', 'POST', server: ['REMOTE_ADDR' => $ip]));

    foreach (range(1, SinalDeAtaque::RAJADA_LIMITE + 5) as $_) {
        $falha('198.51.100.1');
    }
    $falha('198.51.100.2');
    expect(avisosNaOutbox('seguranca.rajada_login'))->toBeEmpty();

    $falha('198.51.100.3');
    $falha('198.51.100.4');

    expect(avisosNaOutbox('seguranca.rajada_login'))->toHaveCount(1);
});

test('senha errada no login de verdade soma para a rajada', function () {
    socratesLigadoNoSite();
    $this->seed(PerfisESeguranca::class);
    contaDoSiteParaAvisos(Perfis::ADMINISTRADOR, 'admin@avisos.test');

    $this->post(route('login'), ['email' => 'admin@avisos.test', 'password' => 'errada', 'captcha_token' => CAPTCHA_DE_TESTE])
        ->assertSessionHasErrors('email');

    $bloco = intdiv(now()->getTimestamp(), SinalDeAtaque::RAJADA_JANELA_MIN * 60);
    expect((int) cache()->get("sinal-de-ataque:rajada-login:{$bloco}"))->toBe(1);
});

test('segundo fator errado em série avisa, com a senha já provada', function () {
    socratesLigadoNoSite();
    $this->seed(PerfisESeguranca::class);
    $admin = contaDoSiteParaAvisos(Perfis::ADMINISTRADOR, 'admin@avisos.test');
    $admin->forceFill([
        'dois_fatores_segredo' => app(TotpService::class)->generateSecretKey(),
        'dois_fatores_confirmado_em' => now(),
    ])->save();

    foreach (range(1, SinalDeAtaque::SEGUNDO_FATOR_LIMITE) as $_) {
        $this->withSession(['2fa.user_id' => $admin->id])
            ->post(route('two-factor.challenge'), ['code' => '000000'])
            ->assertSessionHasErrors('code');
    }

    $eventos = avisosNaOutbox('seguranca.segundo_fator_em_serie');
    expect($eventos)->toHaveCount(1)
        ->and($eventos[0]['resumo'])->toContain('passou pela senha');
});

test('robô no formulário: o décimo captcha recusado avisa, e nada é gravado', function () {
    socratesLigadoNoSite();

    foreach (range(1, SinalDeAtaque::ROBO_LIMITE - 1) as $_) {
        SinaisDoLogin::roboNoFormulario(Request::create('/contato', 'POST'));
    }
    expect(avisosNaOutbox('seguranca.robo_no_formulario'))->toBeEmpty();

    // O último pelo formulário de verdade, com o captcha errado.
    $this->post('/contato', [
        'name' => 'Robô', 'email' => 'robo@spam.test', 'phone' => '21999990000',
        'message' => str_repeat('compre agora ', 6), 'consent' => '1', 'captcha_token' => 'errado',
    ])->assertSessionHasErrors('captcha_token');

    expect(avisosNaOutbox('seguranca.robo_no_formulario'))->toHaveCount(1)
        ->and(DB::table('contatos')->count())->toBe(0);
});

test('IP novo: o primeiro acesso é a linha de base, o segundo IP avisa, o mesmo IP não', function () {
    socratesLigadoNoSite();
    $this->seed(PerfisESeguranca::class);
    $admin = contaDoSiteParaAvisos(Perfis::ADMINISTRADOR, 'admin@avisos.test');

    IpsDeAcesso::registrar($admin, '203.0.113.10');
    IpsDeAcesso::registrar($admin, '203.0.113.10');
    expect(avisosNaOutbox('seguranca.acesso_ip_novo'))->toBeEmpty();

    IpsDeAcesso::registrar($admin, '203.0.113.99');

    $eventos = avisosNaOutbox('seguranca.acesso_ip_novo');
    expect($eventos)->toHaveCount(1)
        ->and($eventos[0]['resumo'])->toContain('203.0.113.99')
        ->and($eventos[0]['resumo'])->toContain('a***@avisos.test')
        ->and(DB::table('ips_de_acesso')->where('ip_hash', 'like', '%203.0.113%')->exists())->toBeFalse()
        ->and(DB::table('ips_de_acesso')->count())->toBe(2);
});

test('IP novo não vale para a redação', function () {
    socratesLigadoNoSite();
    $this->seed(PerfisESeguranca::class);
    $redacao = contaDoSiteParaAvisos(Perfis::REDACAO, 'redacao@avisos.test');

    IpsDeAcesso::registrar($redacao, '203.0.113.10');
    IpsDeAcesso::registrar($redacao, '203.0.113.11');

    expect(avisosNaOutbox())->toBeEmpty()
        ->and(DB::table('ips_de_acesso')->count())->toBe(0);
});

test('o expurgo tira o IP não visto há mais de 12 meses', function () {
    $this->seed(PerfisESeguranca::class);
    $admin = contaDoSiteParaAvisos(Perfis::ADMINISTRADOR, 'admin@avisos.test');
    DB::table('ips_de_acesso')->insert([
        ['user_id' => $admin->id, 'ip_hash' => str_repeat('a', 64), 'primeiro_em' => now()->subYears(2), 'ultimo_em' => now()->subMonths(13)],
        ['user_id' => $admin->id, 'ip_hash' => str_repeat('b', 64), 'primeiro_em' => now()->subYears(2), 'ultimo_em' => now()->subMonth()],
    ]);

    $this->artisan('lgpd:expurgo')->assertSuccessful();

    expect(DB::table('ips_de_acesso')->pluck('ip_hash')->all())->toBe([str_repeat('b', 64)]);
});

test('o filtro de contato tira e-mail e telefone, e deixa passar o e-mail mascarado', function () {
    expect(AvisoAoOperador::semContato('de ana.b+x@site.com.br, (21) 94632-4461'))->toBe('de [e-mail], [número]')
        ->and(AvisoAoOperador::semContato('conta a***@site.com, linha 123'))->toBe('conta a***@site.com, linha 123');
});
