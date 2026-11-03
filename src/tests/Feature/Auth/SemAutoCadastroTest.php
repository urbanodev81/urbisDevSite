<?php

declare(strict_types=1);

/**
 * A rota `/register` do Breeze NÃO existe aqui.
 *
 * Este arquivo existe porque **ausência de rota não quebra teste nenhum
 * sozinha** — precisa de quem a afirme. E a rota volta fácil: por um
 * `breeze:install`, ou por um merge que traga `routes/auth.php` de um irmão.
 * Foi o que aconteceu no BHD.
 *
 * Site de banda com cadastro aberto é um painel de manutenção público.
 */
it('não tem rota de auto-cadastro', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', [])->assertNotFound();

    expect(collect(app('router')->getRoutes())->contains(
        fn ($rota) => $rota->uri() === 'register',
    ))->toBeFalse();
});
