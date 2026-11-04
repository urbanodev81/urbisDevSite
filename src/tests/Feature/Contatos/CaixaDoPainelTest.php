<?php

declare(strict_types=1);

use App\Models\Auditoria;
use App\Models\Contato;
use App\Models\User;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;

/**
 * A tela Contatos do painel (04/11/2026, bloco 2 da 246), por rota e com
 * sessão real, como o PermissoesPorRotaTest.
 */
beforeEach(function () {
    $this->seed(PerfisESeguranca::class);
});

function contaDoPainel(string $perfil): User
{
    $usuario = User::create([
        'name' => 'Conta '.$perfil,
        'email' => 'contatos-'.$perfil.'@teste.local',
        'password' => 'senha-de-teste-123',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);
    $usuario->syncRoles([$perfil]);

    return $usuario;
}

function contatoDeTeste(array $extra = []): Contato
{
    return Contato::create([
        'origem' => 'contato',
        'nome' => 'João Lead',
        'email' => 'joao@exemplo.com',
        'telefone' => '21999990000',
        'mensagem' => 'Preciso de um site.',
        'consentimento_em' => now(),
        ...$extra,
    ]);
}

it('exige login', function () {
    $this->get('/painel/contatos')->assertRedirect(route('login'));
});

it('a redação abre a fila e o detalhe', function () {
    $contato = contatoDeTeste();
    $redacao = contaDoPainel(Perfis::REDACAO);

    $this->actingAs($redacao)->get('/painel/contatos')->assertOk()
        ->assertInertia(fn ($p) => $p->component('Painel/Contatos/Index')->where('contatos.data.0.uuid', $contato->uuid));
    $this->actingAs($redacao)->get('/painel/contatos/'.$contato->uuid)->assertOk()
        ->assertInertia(fn ($p) => $p->component('Painel/Contatos/Detalhe')->where('contato.whatsapp', '5521999990000'));
});

it('a fila padrão mostra só os novos, do mais antigo para o mais novo', function () {
    $this->travel(-2)->days();
    $antigo = contatoDeTeste(['nome' => 'Antigo']);
    $this->travelBack();
    $novo = contatoDeTeste(['nome' => 'Novo', 'email' => 'novo@exemplo.com']);
    contatoDeTeste(['nome' => 'Respondido', 'status' => 'respondido']);

    $this->actingAs(contaDoPainel(Perfis::REDACAO))->get('/painel/contatos')
        ->assertInertia(fn ($p) => $p->has('contatos.data', 2)
            ->where('contatos.data.0.uuid', $antigo->uuid)
            ->where('contatos.data.1.uuid', $novo->uuid));
});

it('mudar situação e nota fica na trilha', function () {
    $contato = contatoDeTeste();

    $this->actingAs(contaDoPainel(Perfis::REDACAO))
        ->put('/painel/contatos/'.$contato->uuid, ['status' => 'respondido', 'nota' => 'Liguei, retorna sexta.'])
        ->assertRedirect();

    expect($contato->fresh()->status->value)->toBe('respondido');
    $campos = Auditoria::query()->where('auditavel_type', Contato::class)->where('evento', 'alterado')->pluck('campo')->all();
    expect($campos)->toContain('status', 'nota');
});

it('recusa situação que não existe', function () {
    $contato = contatoDeTeste();

    $this->actingAs(contaDoPainel(Perfis::REDACAO))
        ->put('/painel/contatos/'.$contato->uuid, ['status' => 'fechado'])
        ->assertSessionHasErrors('status');
});

it('arquivar não apaga, e restaurar devolve', function () {
    $contato = contatoDeTeste();
    $conta = contaDoPainel(Perfis::REDACAO);

    $this->actingAs($conta)->delete('/painel/contatos/'.$contato->uuid)->assertRedirect(route('painel.contatos.index'));
    expect(Contato::withTrashed()->find($contato->id)->trashed())->toBeTrue();

    $this->actingAs($conta)->get('/painel/contatos?arquivados=1')
        ->assertInertia(fn ($p) => $p->where('contatos.data.0.uuid', $contato->uuid));
    $this->actingAs($conta)->get('/painel/contatos/'.$contato->uuid)->assertOk();

    $this->actingAs($conta)->patch('/painel/contatos/'.$contato->uuid.'/restaurar')->assertRedirect();
    expect($contato->fresh()->trashed())->toBeFalse();
});

it('exporta tudo o que existe com o e-mail do titular, inclusive arquivado', function () {
    $um = contatoDeTeste();
    contatoDeTeste(['email' => 'JOAO@exemplo.com', 'mensagem' => 'Segundo recado.'])->delete();
    contatoDeTeste(['email' => 'outra@exemplo.com']);

    $resposta = $this->actingAs(contaDoPainel(Perfis::REDACAO))->get('/painel/contatos/'.$um->uuid.'/exportar')->assertOk();

    expect($resposta->headers->get('Content-Disposition'))->toContain('attachment')
        ->and($resposta->json('registros'))->toHaveCount(2);
});

it('endereço com uuid torto é 404, não erro de banco', function () {
    $this->actingAs(contaDoPainel(Perfis::REDACAO))->get('/painel/contatos/nao-e-uuid')->assertNotFound();
});

it('o início mostra quem espera resposta', function () {
    contatoDeTeste();

    $this->actingAs(contaDoPainel(Perfis::REDACAO))->get('/painel')
        ->assertInertia(fn ($p) => $p->where('contatos.novos', 1)->has('contatos.esperando', 1));
});
