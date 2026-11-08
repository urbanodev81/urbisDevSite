<?php

declare(strict_types=1);

use App\Models\Auditoria;
use App\Models\Pergunta;
use App\Models\User;
use App\Support\Perfis;
use App\Support\PerguntasDoSite;
use Database\Seeders\PerfisESeguranca;

/**
 * Perguntas de "Como trabalhamos" (bloco 4d da 246, 08/11/2026). O que se
 * prova: a migration semeia as 7 de sempre e a página fica igual à de antes; o
 * que se grava aparece, escapado; o título conta sozinho; oculta some; a ordem
 * muda; sem nenhuma, a seção some; sem `FAQPage`; só o administrador mexe;
 * tudo vai para a trilha; banco fora → as 7 de sempre.
 */
beforeEach(function () {
    $this->seed(PerfisESeguranca::class);
    PerguntasDoSite::esquecer();
});

function contaDasPerguntas(string $perfil): User
{
    $usuario = User::create([
        'name' => 'Conta '.$perfil,
        'email' => 'perguntas-'.$perfil.'@teste.local',
        'password' => 'senha-de-teste-123',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);
    $usuario->syncRoles([$perfil]);

    return $usuario;
}

it('a migration semeia as 7 de sempre, e a página fica como era', function () {
    expect(Pergunta::orderBy('ordem')->pluck('pergunta')->all())->toBe(array_column(PerguntasDoSite::PADRAO, 'pergunta'));

    $this->get('/como-trabalhamos')->assertOk()
        ->assertSee('Sete perguntas para fazer a qualquer fornecedor')
        ->assertSeeInOrder([
            '<div class="pergunta-n">01</div><p>Como vocês descobrem que o sistema caiu?<span>Se a resposta for “o cliente avisa”, o custo da queda é seu.</span></p>',
            '<div class="pergunta-n">07</div><p>Se eu quiser trocar de fornecedor, o que sai comigo?<span>Código, dados e documentação — ou dependência.</span></p>',
        ], false)
        ->assertDontSee('FAQPage');
});

it('o administrador adiciona, e a pergunta aparece escapada, com o título recontado', function () {
    $admin = contaDasPerguntas(Perfis::ADMINISTRADOR);

    $this->actingAs($admin)->get('/painel/site/perguntas')->assertOk()
        ->assertInertia(fn ($p) => $p->component('Painel/Perguntas/Index')->has('perguntas', 7));

    $this->actingAs($admin)->post('/painel/site/perguntas', ['pergunta' => 'Quem paga o <b>servidor</b>?', 'explicacao' => '', 'ativo' => true])
        ->assertRedirect()->assertSessionHasNoErrors();

    $nova = Pergunta::where('pergunta', 'Quem paga o <b>servidor</b>?')->firstOrFail();
    expect($nova->ordem)->toBe(8)->and($nova->explicacao)->toBeNull();

    $this->get('/como-trabalhamos')
        ->assertSee('Oito perguntas para fazer a qualquer fornecedor')
        ->assertSee('<div class="pergunta-n">08</div><p>Quem paga o &lt;b&gt;servidor&lt;/b&gt;?</p>', false);

    expect(Auditoria::where('auditavel_type', Pergunta::class)->where('evento', 'criado')->count())->toBe(1);
});

it('oculta some, e o título acompanha', function () {
    $admin = contaDasPerguntas(Perfis::ADMINISTRADOR);
    $tela = Pergunta::where('pergunta', 'A tela funciona para quem enxerga pouco?')->firstOrFail();

    $this->actingAs($admin)->put("/painel/site/perguntas/{$tela->id}", [...$tela->only(['pergunta', 'explicacao']), 'ativo' => false])
        ->assertSessionHasNoErrors();

    $this->get('/como-trabalhamos')->assertSee('Seis perguntas')->assertDontSee('A tela funciona para quem enxerga pouco?');
    expect(Auditoria::where('auditavel_type', Pergunta::class)->where('campo', 'ativo')->exists())->toBeTrue();
});

it('sem nenhuma ativa, a seção some sem quebrar a página', function () {
    Pergunta::query()->update(['ativo' => false]);
    PerguntasDoSite::esquecer();

    $this->get('/como-trabalhamos')->assertOk()->assertDontSee('id="perguntas"', false);
});

it('subir e descer trocam a posição com a vizinha', function () {
    $admin = contaDasPerguntas(Perfis::ADMINISTRADOR);
    $segunda = Pergunta::orderBy('ordem')->skip(1)->firstOrFail();

    $this->actingAs($admin)->post("/painel/site/perguntas/{$segunda->id}/mover", ['direcao' => 'subir'])->assertRedirect();
    expect(Pergunta::orderBy('ordem')->value('id'))->toBe($segunda->id);
    $this->get('/como-trabalhamos')->assertSeeInOrder(['O que acontece se a pessoa', 'Como vocês descobrem']);

    $this->actingAs($admin)->post("/painel/site/perguntas/{$segunda->id}/mover", ['direcao' => 'para-o-lado'])
        ->assertSessionHasErrors('direcao');
});

it('recusa pergunta vazia ou longa demais, com a mensagem em português', function () {
    $admin = contaDasPerguntas(Perfis::ADMINISTRADOR);

    $this->actingAs($admin)->post('/painel/site/perguntas', ['pergunta' => '', 'ativo' => true])
        ->assertSessionHasErrors(['pergunta' => 'O campo pergunta é obrigatório.']);
    $this->actingAs($admin)->post('/painel/site/perguntas', ['pergunta' => str_repeat('a', 201), 'ativo' => true])
        ->assertSessionHasErrors(['pergunta' => 'Passou do limite de 200 letras.']);

    expect(Pergunta::count())->toBe(7);
});

it('remove de vez, e a trilha guarda', function () {
    $admin = contaDasPerguntas(Perfis::ADMINISTRADOR);
    $uma = Pergunta::firstOrFail();

    $this->actingAs($admin)->delete("/painel/site/perguntas/{$uma->id}")->assertRedirect();

    expect(Pergunta::count())->toBe(6)
        ->and(Auditoria::where('auditavel_type', Pergunta::class)->where('evento', 'removido')->count())->toBe(1);
});

it('a redação não abre nem grava perguntas', function () {
    $redacao = contaDasPerguntas(Perfis::REDACAO);
    $uma = Pergunta::firstOrFail();
    $dados = ['pergunta' => 'Outra?', 'ativo' => true];

    $this->actingAs($redacao)->get('/painel/site/perguntas')->assertForbidden();
    $this->actingAs($redacao)->post('/painel/site/perguntas', $dados)->assertForbidden();
    $this->actingAs($redacao)->put("/painel/site/perguntas/{$uma->id}", $dados)->assertForbidden();
    $this->actingAs($redacao)->post("/painel/site/perguntas/{$uma->id}/mover", ['direcao' => 'descer'])->assertForbidden();
    $this->actingAs($redacao)->delete("/painel/site/perguntas/{$uma->id}")->assertForbidden();
});

it('com o banco fora, a página mostra as 7 de sempre', function () {
    Pergunta::orderBy('ordem')->first()->update(['pergunta' => 'Pergunta só do banco']);
    PerguntasDoSite::esquecer();

    config([
        'database.connections.sem_banco' => [...config('database.connections.pgsql'), 'host' => '127.0.0.1', 'port' => 1],
        'database.default' => 'sem_banco',
    ]);

    try {
        $this->get('/como-trabalhamos')->assertOk()->assertSee('Sete perguntas')
            ->assertSee('Como vocês descobrem que o sistema caiu?')->assertDontSee('Pergunta só do banco');
    } finally {
        config(['database.default' => 'pgsql']);
    }
});
