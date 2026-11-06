<?php

declare(strict_types=1);

use App\Models\Auditoria;
use App\Models\Servico;
use App\Models\User;
use App\Support\Perfis;
use App\Support\ServicosDoSite;
use Database\Seeders\PerfisESeguranca;

/**
 * Serviços de "O que fazemos" (bloco 4b da 246, 06/11/2026). O que se prova:
 * a migration semeia os 4 de sempre; o que se grava aparece na home, no
 * `OfferCatalog` do JSON-LD e no `llms.txt`; oculto some dos três; a ordem
 * muda; ícone, cor e link fora da regra são recusados; só o administrador
 * mexe; tudo vai para a trilha. O "banco fora" está no fim deste arquivo e no
 * `SitePublicoSemBancoTest`.
 */
beforeEach(function () {
    $this->seed(PerfisESeguranca::class);
    ServicosDoSite::esquecer();
});

function contaDosServicos(string $perfil): User
{
    $usuario = User::create([
        'name' => 'Conta '.$perfil,
        'email' => 'servicos-'.$perfil.'@teste.local',
        'password' => 'senha-de-teste-123',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);
    $usuario->syncRoles([$perfil]);

    return $usuario;
}

function servicoValido(array $troca = []): array
{
    return [
        'titulo' => 'Lojas virtuais',
        'frente' => 'Comércio',
        'texto' => 'Loja com catálogo, carrinho e pagamento, que o lojista mesmo atualiza.',
        'icone' => 'carrinho',
        'cor' => 'verde',
        'situacao' => 'em_construcao',
        'link_url' => '',
        'link_rotulo' => '',
        'nome_no_catalogo' => 'Loja virtual com painel próprio',
        'ativo' => true,
        ...$troca,
    ];
}

it('a migration semeia os 4 cartões de sempre, na ordem', function () {
    expect(Servico::orderBy('ordem')->pluck('titulo')->all())->toBe(array_column(ServicosDoSite::PADRAO, 'titulo'));

    $this->get('/')->assertOk()->assertSeeInOrder([
        'Frente · <b>Imobiliário</b>', 'Imobiliárias', 'Conhecer o USI&nbsp;&rarr;',
        'Sites que você mesmo atualiza', 'Clínicas de terapia', 'Sindicatos e associações',
    ], false);
    $this->get('/')->assertSee('"name":"USI: site e sistema de gestão para imobiliárias"', false);
    $this->get('/llms.txt')->assertSee('- Imobiliárias (no ar): O USI é o nosso sistema imobiliário', false)
        ->assertSee('[Conhecer o USI](https://usi.urbisdev.tech)', false);
});

it('o administrador adiciona, e o serviço aparece na home, no JSON-LD e no llms.txt', function () {
    $admin = contaDosServicos(Perfis::ADMINISTRADOR);

    $this->actingAs($admin)->get('/painel/site/servicos')->assertOk()
        ->assertInertia(fn ($p) => $p->component('Painel/Servicos/Index')->has('servicos', 4)->has('icones'));

    $this->actingAs($admin)->post('/painel/site/servicos', servicoValido(['texto' => "Loja d'água <b>forte</b>."]))
        ->assertRedirect()->assertSessionHasNoErrors();

    $novo = Servico::where('titulo', 'Lojas virtuais')->firstOrFail();
    expect($novo->ordem)->toBe(5)->and($novo->link_url)->toBeNull();

    $this->get('/')->assertSee('Frente · <b>Comércio</b>', false)
        ->assertSee('Loja d&#039;água &lt;b&gt;forte&lt;/b&gt;.', false)
        ->assertSee('"name":"Loja virtual com painel próprio"', false)
        ->assertSee('Em construção."', false);
    $this->get('/llms.txt')->assertSee("- Lojas virtuais (em construção): Loja d'água <b>forte</b>.", false);

    expect(Auditoria::where('auditavel_type', Servico::class)->where('evento', 'criado')->count())->toBe(1);
});

it('oculto some dos três lugares, e volta ao mostrar', function () {
    $admin = contaDosServicos(Perfis::ADMINISTRADOR);
    $clinica = Servico::where('titulo', 'Clínicas de terapia')->firstOrFail();

    $this->actingAs($admin)->put("/painel/site/servicos/{$clinica->id}", [...$clinica->toArray(), 'ativo' => false])
        ->assertSessionHasNoErrors();

    $this->get('/')->assertDontSee('Clínicas de terapia')->assertDontSee('clínicas de terapia');
    $this->get('/llms.txt')->assertDontSee('Clínicas de terapia');

    expect(Auditoria::where('auditavel_type', Servico::class)->where('campo', 'ativo')->exists())->toBeTrue();
});

it('sem nenhum serviço ativo, a seção e o catálogo somem sem quebrar a página', function () {
    Servico::query()->update(['ativo' => false]);
    ServicosDoSite::esquecer();

    $this->get('/')->assertOk()->assertDontSee('id="fazemos"', false)->assertDontSee('hasOfferCatalog', false);
    $this->get('/llms.txt')->assertOk()->assertDontSee('## O que fazemos');
});

it('subir e descer trocam a posição com o vizinho', function () {
    $admin = contaDosServicos(Perfis::ADMINISTRADOR);
    $sites = Servico::where('titulo', 'Sites que você mesmo atualiza')->firstOrFail();

    $this->actingAs($admin)->post("/painel/site/servicos/{$sites->id}/mover", ['direcao' => 'subir'])->assertRedirect();
    expect(Servico::orderBy('ordem')->pluck('titulo')->take(2)->all())->toBe(['Sites que você mesmo atualiza', 'Imobiliárias']);
    $this->get('/')->assertSeeInOrder(['Sites que você mesmo atualiza', 'Imobiliárias']);

    // O primeiro não sobe mais; nada muda.
    $this->actingAs($admin)->post("/painel/site/servicos/{$sites->id}/mover", ['direcao' => 'subir'])->assertRedirect();
    expect(Servico::orderBy('ordem')->value('titulo'))->toBe('Sites que você mesmo atualiza');

    $this->actingAs($admin)->post("/painel/site/servicos/{$sites->id}/mover", ['direcao' => 'para-o-lado'])
        ->assertSessionHasErrors('direcao');
});

it('recusa ícone, cor e selo fora da lista, e link sem https', function () {
    $admin = contaDosServicos(Perfis::ADMINISTRADOR);

    $this->actingAs($admin)->post('/painel/site/servicos', servicoValido([
        'icone' => '<script>', 'cor' => 'red; background:url(x)', 'situacao' => 'talvez',
        'link_url' => 'javascript:alert(1)', 'link_rotulo' => 'Ver',
    ]))->assertSessionHasErrors(['icone', 'cor', 'situacao', 'link_url']);

    $this->actingAs($admin)->post('/painel/site/servicos', servicoValido(['link_url' => 'http://exemplo.com', 'link_rotulo' => 'Ver']))
        ->assertSessionHasErrors('link_url');

    $this->actingAs($admin)->post('/painel/site/servicos', servicoValido(['link_url' => 'https://exemplo.com']))
        ->assertSessionHasErrors('link_rotulo');

    expect(Servico::count())->toBe(4);
});

it('remove de vez, e a trilha guarda', function () {
    $admin = contaDosServicos(Perfis::ADMINISTRADOR);
    $ssb = Servico::where('titulo', 'Sindicatos e associações')->firstOrFail();

    $this->actingAs($admin)->delete("/painel/site/servicos/{$ssb->id}")->assertRedirect();

    expect(Servico::count())->toBe(3)
        ->and(Auditoria::where('auditavel_type', Servico::class)->where('evento', 'removido')->count())->toBe(1);
    $this->get('/')->assertDontSee('Sindicatos e associações');
});

it('a redação não abre nem grava serviços', function () {
    $redacao = contaDosServicos(Perfis::REDACAO);
    $um = Servico::firstOrFail();

    $this->actingAs($redacao)->get('/painel/site/servicos')->assertForbidden();
    $this->actingAs($redacao)->post('/painel/site/servicos', servicoValido())->assertForbidden();
    $this->actingAs($redacao)->put("/painel/site/servicos/{$um->id}", servicoValido())->assertForbidden();
    $this->actingAs($redacao)->post("/painel/site/servicos/{$um->id}/mover", ['direcao' => 'descer'])->assertForbidden();
    $this->actingAs($redacao)->delete("/painel/site/servicos/{$um->id}")->assertForbidden();
});

it('com o banco fora, a home mostra os 4 de sempre', function () {
    Servico::where('titulo', 'Imobiliárias')->update(['titulo' => 'Título só do banco']);
    ServicosDoSite::esquecer();

    config([
        'database.connections.sem_banco' => [...config('database.connections.pgsql'), 'host' => '127.0.0.1', 'port' => 1],
        'database.default' => 'sem_banco',
    ]);

    try {
        $this->get('/')->assertOk()->assertSee('Imobiliárias')->assertDontSee('Título só do banco')
            ->assertSee('Sindicatos e associações');
        $this->get('/llms.txt')->assertOk()->assertSee('- Clínicas de terapia (em construção):', false);
    } finally {
        config(['database.default' => 'pgsql']);
    }
});
