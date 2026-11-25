<?php

declare(strict_types=1);

/**
 * Topo da home com movimento (24/11/2026), que virou o palco da vitrine do
 * urbanodev.com.br em 25/11/2026: logo no centro, palavras com física e
 * layouts passando ao fundo, em `public/topo.js`. Os prints são
 * montados pelo script a partir de uma lista de nomes; um nome sem arquivo
 * vira um quadro quebrado no fundo, sem erro em lugar nenhum. Este teste
 * segura as duas pontas: a home chama o script, e todo print citado existe.
 */
it('a home carrega o topo.js e só ela', function () {
    expect($this->get('/')->assertOk()->getContent())->toContain('<script src="/topo.js" defer></script>');
    expect($this->get('/como-trabalhamos')->assertOk()->getContent())->not->toContain('/topo.js');
});

it('todo print que o topo.js cita existe em img/vitrine', function () {
    $js = file_get_contents(public_path('topo.js'));
    preg_match('#var PRINTS = \[(.*?)\];#s', $js, $bloco);
    preg_match_all("#'([a-z0-9-]+)'#", $bloco[1], $nomes);

    expect($nomes[1])->toHaveCount(15);

    foreach ($nomes[1] as $nome) {
        expect(public_path("img/vitrine/{$nome}.webp"))->toBeFile();
    }
});

it('o topo é o palco: escuro nos dois temas, o título do painel como lema e os dois botões', function () {
    $html = $this->get('/')->assertOk()->getContent();

    // `.sempre-escuro` declara os tokens do escuro no próprio palco.
    expect($html)->toContain('class="palco sempre-escuro" id="palco"')
        ->and($html)->toMatch('/\.sempre-escuro,\s*:root\s*\{/')
        ->and($html)->toMatch('/:root, html\.claro, \.sempre-escuro\s*\{/')
        // O título do painel é o h1, sob o logo.
        ->and($html)->toContain('<h1 class="lema" id="lema">Software que resolve <em>problemas reais</em>.</h1>');

    $palco = substr($html, strpos($html, 'id="palco"'), 4000);
    expect($palco)->toContain('class="btn btn-primary" href="#contato">Fale conosco')
        ->and($palco)->toContain('class="btn btn-ghost" href="#demonstracoes">Veja funcionando');
});

it('a etiqueta e o texto do topo antigo saíram do painel', function () {
    $catalogo = (new ReflectionClassConstant(\App\Support\ConfiguracaoDoSite::class, 'CATALOGO'))->getValue();

    expect($catalogo)->toHaveKey('home.hero_titulo')
        ->not->toHaveKey('home.hero_etiqueta')
        ->not->toHaveKey('home.hero_texto');
});
