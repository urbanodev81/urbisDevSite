<?php

declare(strict_types=1);

/**
 * As fontes moram no próprio site (03/11/2026, pendência 248).
 *
 * O CSS do fonts.bunny.net passou a mandar buscar a Bricolage no
 * cdn.jsdelivr.net, a CSP barrava e os títulos caíam na fonte reserva — sem
 * erro na tela, só a cara errada. Este teste segura as três pontas: a CSP só
 * aceita fonte daqui, nenhum layout volta a pedir fonte de fora, e todo
 * arquivo citado no fonts.css existe.
 */
it('a CSP só aceita fonte e estilo do próprio site', function () {
    $csp = $this->get('/')->assertOk()->headers->get('Content-Security-Policy');

    expect($csp)->toContain("font-src 'self';")
        ->and($csp)->toContain("style-src 'self' 'unsafe-inline';")
        ->and($csp)->not->toContain('bunny')
        ->and($csp)->not->toContain('jsdelivr');
});

it('a página carrega o fonts.css daqui, e nenhum CSS de fonte de fora', function (string $url) {
    $html = $this->get($url)->assertOk()->getContent();

    expect($html)->toContain('href="/fonts/fonts.css"')
        ->and($html)->not->toContain('fonts.bunny.net')
        ->and($html)->not->toContain('fonts.googleapis.com');
})->with(['/', '/painel/login']);

it('todo arquivo citado no fonts.css existe', function () {
    $css = file_get_contents(public_path('fonts/fonts.css'));
    preg_match_all('#url\(/fonts/([^)]+)\)#', $css, $m);

    expect($m[1])->toHaveCount(16);

    foreach ($m[1] as $arquivo) {
        expect(public_path('fonts/'.$arquivo))->toBeFile();
    }
});
