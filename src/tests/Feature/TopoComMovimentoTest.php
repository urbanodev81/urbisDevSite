<?php

declare(strict_types=1);

/**
 * Topo da home com movimento (24/11/2026): palavras com física, logo que se
 * desenha e layouts passando ao fundo, em `public/topo.js`. Os prints são
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
