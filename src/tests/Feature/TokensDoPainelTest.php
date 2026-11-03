<?php

declare(strict_types=1);

/**
 * As cores do painel são as do site (03/11/2026, pendência 246).
 *
 * `resources/css/tokens-do-painel.css` é GERADO de
 * `resources/views/partials/tokens.blade.php` pelo script
 * `resources/css/tokens-do-painel.py`. Mudou um token do site e esqueceu de
 * rodar o script? Este teste acusa — antes do painel ficar com outra paleta.
 */
it('o CSS do painel bate com os tokens do site', function () {
    $site = file_get_contents(resource_path('views/partials/tokens.blade.php'));
    $painel = file_get_contents(resource_path('css/tokens-do-painel.css'));

    $trio = function (string $hex): string {
        [$r, $g, $b] = sscanf(ltrim($hex, '#'), '%02x%02x%02x');

        return "{$r} {$g} {$b}";
    };

    $bloco = function (string $css, string $seletor): string {
        preg_match('/'.preg_quote($seletor, '/').'\s*\{(.*?)\n\}/s', $css, $m);

        return $m[1] ?? '';
    };

    $pares = ['bg' => 'bg', 'surface' => 'surface', 'fg' => 'fg', 'fg-muted' => 'fg-muted',
        'border' => 'border', 'accent' => 'accent', 'success' => 'ok', 'danger' => 'error'];

    foreach ([':root' => ":root,\nhtml.claro .dark", 'html.claro' => "\nhtml.claro"] as $doSite => $doPainel) {
        $origem = $bloco($site, $doSite);
        $destino = $bloco($painel, $doPainel);

        foreach ($pares as $nomePainel => $nomeSite) {
            preg_match('/--'.preg_quote($nomeSite, '/').':\s*(#[0-9A-Fa-f]{6})/', $origem, $hex);
            expect($hex)->not->toBeEmpty("token --{$nomeSite} sumiu de {$doSite} no site");
            expect($destino)->toContain("--{$nomePainel}: ".$trio($hex[1]).';');
        }
    }
});

/**
 * Todo `route('...')` que o JavaScript do painel chama existe no Laravel.
 * Nome errado não quebra o build: estoura no clique, na mão de quem usa
 * (o Duo ficou sem "Enviar" no relato de erro por isso, 15/09/2026).
 */
it('toda rota que o front chama existe', function () {
    $arquivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('js')));
    $nomes = [];

    foreach ($arquivos as $arquivo) {
        if (in_array($arquivo->getExtension(), ['vue', 'js'], true)) {
            preg_match_all("/route\\('([^']+)'/", file_get_contents($arquivo->getPathname()), $m);
            $nomes = [...$nomes, ...$m[1]];
        }
    }

    expect($nomes)->not->toBeEmpty();

    foreach (array_unique($nomes) as $nome) {
        expect(app('router')->has($nome))->toBeTrue("route('{$nome}') não existe");
    }
});
