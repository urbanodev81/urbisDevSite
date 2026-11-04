<?php

declare(strict_types=1);

use App\Models\Contato;
use App\Models\SocratesOutbox;
use App\Services\Socrates\OutboxDoSocrates;
use App\Support\Contatos\CaixaDeContatos;
use Database\Seeders\PoliticasDeRetencao;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * Expurgo LGPD dos contatos e a outbox do Sócrates (04/11/2026, bloco 2 da 246).
 */
function contatoDe(int $mesesAtras): Contato
{
    $contato = Contato::create([
        'origem' => 'contato', 'nome' => 'X', 'email' => 'x@exemplo.com', 'telefone' => '21999990000',
    ]);
    $contato->forceFill(['created_at' => now()->subMonths($mesesAtras)])->saveQuietly();

    return $contato;
}

it('o expurgo apaga de verdade o que passou de 24 meses, inclusive arquivado', function () {
    $this->seed(PoliticasDeRetencao::class);
    $velho = contatoDe(25);
    $velhoArquivado = contatoDe(30);
    $velhoArquivado->delete();
    $recente = contatoDe(23);

    $this->artisan('lgpd:expurgo')->assertSuccessful();

    expect(Contato::withTrashed()->pluck('id')->all())->toBe([$recente->id]);
});

it('o expurgo em simulação não apaga nada', function () {
    $this->seed(PoliticasDeRetencao::class);
    contatoDe(25);

    $this->artisan('lgpd:expurgo --dry-run')->expectsOutputToContain('contato: 1 linha')->assertSuccessful();

    expect(Contato::withTrashed()->count())->toBe(1);
});

it('a migration deixa as políticas gravadas', function () {
    expect(\App\Models\PoliticaRetencao::query()->pluck('meses', 'recurso')->all())
        ->toBe(['auditoria' => 24, 'contato' => 24]);
});

it('com SOCRATES_LEADS desligado, lead não entra na outbox', function () {
    app()->detectEnvironment(fn () => 'local');
    config(['socrates.url' => 'https://socrates.teste', 'socrates.token' => 't', 'socrates.leads' => false]);

    app(CaixaDeContatos::class)->contarAoSocrates(contatoDe(0));

    expect(SocratesOutbox::count())->toBe(0);
});

it('com SOCRATES_LEADS ligado, o lead vai ao Sócrates com chave de idempotência e sai da outbox', function () {
    app()->detectEnvironment(fn () => 'local');
    config(['socrates.url' => 'https://socrates.teste', 'socrates.token' => 't', 'socrates.leads' => true]);
    Http::fake(['socrates.teste/*' => Http::response(['ok' => true], 201)]);

    $contato = contatoDe(0);
    $linha = app(OutboxDoSocrates::class)->registrar(OutboxDoSocrates::LEAD_RECEBIDO, ['id' => $contato->uuid]);
    $r = app(OutboxDoSocrates::class)->entregarPendentes();

    expect($r['entregues'])->toBe(1)->and(SocratesOutbox::count())->toBe(0);
    Http::assertSent(fn (Request $req) => $req->url() === 'https://socrates.teste/api/site/leads'
        && $req->header('Idempotency-Key')[0] === $linha->id);
});

it('Sócrates fora: a linha fica para a próxima rodada', function () {
    app()->detectEnvironment(fn () => 'local');
    config(['socrates.url' => 'https://socrates.teste', 'socrates.token' => 't', 'socrates.leads' => true]);
    Http::fake(['socrates.teste/*' => Http::response('', 503)]);

    app(OutboxDoSocrates::class)->registrar(OutboxDoSocrates::LEAD_RECEBIDO, ['id' => 'x']);
    $r = app(OutboxDoSocrates::class)->entregarPendentes();

    expect($r['adiadas'])->toBe(1)->and(SocratesOutbox::sole()->ultimo_erro)->toBe('HTTP 503');
});
