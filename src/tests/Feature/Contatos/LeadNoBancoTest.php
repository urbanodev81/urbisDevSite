<?php

declare(strict_types=1);

use App\Models\Contato;
use Illuminate\Support\Facades\Mail;

/**
 * O lead é gravado ANTES do e-mail (04/11/2026, bloco 2 da 246).
 *
 * O defeito que isto fecha: com o SMTP fora, o lead vivia só numa linha do
 * `laravel.log`. Agora ele mora no banco, e o e-mail é aviso. E o site
 * público continua sem depender do banco: com o Postgres fora, o formulário
 * volta ao caminho de antes.
 */
function recado(array $extra = []): array
{
    return [
        'name' => 'Maria Teste',
        'email' => 'maria@exemplo.com',
        'phone' => '(21) 99999-0000',
        'message' => str_repeat('Quero um sistema para a minha loja. ', 3),
        'consent' => '1',
        'captcha_token' => CAPTCHA_DE_TESTE,
        ...$extra,
    ];
}

function semBanco(): void
{
    config([
        'database.connections.sem_banco' => [...config('database.connections.pgsql'), 'host' => '127.0.0.1', 'port' => 1],
        'database.default' => 'sem_banco',
    ]);
}

afterEach(function () {
    config(['database.default' => 'pgsql']);
});

it('grava o contato do formulário e marca que o aviso saiu', function () {
    Mail::fake();

    $this->from('/')->post('/contato', recado())->assertRedirect('/')->assertSessionHas('success');

    $contato = Contato::sole();
    expect($contato->origem)->toBe('contato')
        ->and($contato->nome)->toBe('Maria Teste')
        ->and($contato->status->value)->toBe('novo')
        ->and($contato->consentimento_em)->not->toBeNull()
        ->and($contato->email_enviado_em)->not->toBeNull()
        ->and($contato->email_erro)->toBeNull();
});

it('guarda de onde veio só quando o slug existe', function () {
    Mail::fake();

    $this->from('/')->post('/contato', recado(['de_demo' => 'planilha', 'de_texto' => 'texto-que-nao-existe']));

    $contato = Contato::sole();
    expect($contato->demo)->toBe('planilha')->and($contato->texto)->toBeNull();
});

it('com o e-mail fora, o lead fica no banco e a pessoa vê sucesso', function () {
    Mail::shouldReceive('send')->andThrow(new RuntimeException('535 Authentication failed'));

    $this->from('/')->post('/contato', recado())->assertRedirect('/')->assertSessionHas('success');

    $contato = Contato::sole();
    expect($contato->email_enviado_em)->toBeNull()
        ->and($contato->email_erro)->toContain('535')
        ->and($contato->emailFalhou())->toBeTrue();
});

it('com o banco fora, o formulário segue pelo e-mail como antes', function () {
    Mail::fake();
    semBanco();

    $this->from('/')->post('/contato', recado())->assertRedirect('/')->assertSessionHas('success');
});

it('com banco E e-mail fora, a pessoa é avisada e pode tentar de novo', function () {
    Mail::shouldReceive('send')->andThrow(new RuntimeException('535'));
    semBanco();

    $this->from('/')->post('/contato', recado())->assertRedirect('/')->assertSessionHasErrors('envio');
});

it('grava o lead da porta de uma demonstração', function () {
    Mail::fake();

    $this->from('/demonstracoes/planilha')
        ->post('/demonstracoes/planilha/liberar', collect(recado())->except('message')->all())
        ->assertRedirect('/demonstracoes/planilha#ferramenta');

    $contato = Contato::sole();
    expect($contato->origem)->toBe('demonstracao')
        ->and($contato->demo)->toBe('planilha')
        ->and($contato->mensagem)->toBeNull()
        ->and($contato->email_enviado_em)->not->toBeNull();
});

it('a demonstração abre mesmo com o e-mail fora, e o lead fica no banco', function () {
    Mail::shouldReceive('send')->andThrow(new RuntimeException('535'));

    $this->from('/demonstracoes/planilha')
        ->post('/demonstracoes/planilha/liberar', collect(recado())->except('message')->all())
        ->assertRedirect('/demonstracoes/planilha#ferramenta');

    expect(Contato::sole()->emailFalhou())->toBeTrue();
});

it('a criação entra na trilha sem copiar o dado pessoal', function () {
    Mail::fake();

    $this->from('/')->post('/contato', recado());

    $linha = \App\Models\Auditoria::query()->where('auditavel_type', Contato::class)->sole();
    expect($linha->evento)->toBe('criado')
        ->and($linha->de)->toBeNull()
        ->and($linha->para)->toBeNull();
});
