<?php

declare(strict_types=1);

/**
 * O site público NÃO depende do banco (03/11/2026, pendência 246).
 *
 * O banco chegou com o painel; as páginas públicas seguem lendo arquivos,
 * config e o portfólio do Sócrates. Se o Postgres cair, o site fica de pé.
 * Este teste derruba a conexão (aponta para um host que não existe) e pede
 * cada página pública: qualquer consulta escondida num layout vira 500 aqui.
 */
beforeEach(function () {
    // Uma conexão que recusa na hora (porta 1 de 127.0.0.1) vira a padrão.
    // A `pgsql` de verdade fica intacta para o RefreshDatabase desfazer.
    config([
        'database.connections.sem_banco' => [
            ...config('database.connections.pgsql'),
            'host' => '127.0.0.1',
            'port' => 1,
        ],
        'database.default' => 'sem_banco',
    ]);
});

afterEach(function () {
    config(['database.default' => 'pgsql']);
});

it('abre a página pública sem banco', function (string $url) {
    $this->get($url)->assertOk();
})->with([
    '/',
    '/como-trabalhamos',
    '/demonstracoes',
    '/blog',
    '/sitemap.xml',
    '/llms.txt',
]);

it('a 404 do site também abre sem banco', function () {
    $this->get('/pagina-que-nao-existe')->assertNotFound();
});
