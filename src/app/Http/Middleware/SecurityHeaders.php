<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeçalhos de segurança do site — até 29/09/2026 ele não mandava nenhum
 * (pendência 134), e os produtos da casa mandam todos.
 *
 * A CSP passou 18/10 a 31/10/2026 em REPORT-ONLY (pendência 136): o navegador
 * só contava ao `POST /csp-relato` o que teria barrado, e o
 * `CspRelatoController` guardava a lista em `storage/logs/csp-*.log`. A medição
 * acusou só código nosso: os `<script>` embutidos (tema, menu, acessibilidade,
 * progresso, home) e os `onclick=` do menu e do modal. Os `onclick=` viraram
 * ouvintes de evento, e cada `<script>` embutido leva o nonce desta requisição:
 * `<script nonce="{{ $cspNonce ?? '' }}">`. Desde 31/10 a política BARRA, e o
 * relato continua ligado para mostrar o que quebrar.
 *
 * Script embutido novo sem o nonce não roda. O sintoma é a página ficar sem o
 * comportamento, e o log `csp-*.log` mostra o começo do script barrado.
 * O que aparece lá de `use.typekit.net` e `font-src data` não é nosso: vem de
 * extensão de navegador do visitante.
 *
 *  - `'report-sample'`: o relato traz os primeiros 40 caracteres do script
 *    barrado — é o que diz QUAL script embutido é, sem depender da linha.
 *  - Fontes só de `'self'` (03/11/2026, pendência 248): as três famílias moram
 *    em `public/fonts`. O bunny.net passou a mandar buscar a Bricolage no
 *    `cdn.jsdelivr.net`, a política barrava e os títulos caíam na reserva.
 *  - `style-src 'unsafe-inline'`: cada página traz o seu `<style>` por
 *    `@push('estilos')`, e há `style=` solto. Estilo embutido não executa
 *    código; o que a política protege de verdade é o `script-src`.
 *  - `worker-src blob: data:`: o Altcha faz o trabalho do captcha num Worker
 *    criado de `blob:` ou de `data:` (`altcha-3.2.2.min.js`).
 *  - `img-src data: blob:`: SVG embutido em CSS e o logo que o visitante sobe
 *    na demonstração `sites`, que só existe como `blob:` no aparelho dele.
 *
 * As fichas estáticas de `public/demos/lancamento/` não passam por aqui: o
 * nginx as serve direto.
 */
class SecurityHeaders
{
    private const POLITICA = [
        "default-src 'self'",
        "style-src 'self' 'unsafe-inline'",
        "font-src 'self'",
        "img-src 'self' data: blob:",
        "connect-src 'self'",
        "worker-src 'self' blob: data:",
        "frame-src 'none'",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'self'",
        'report-uri /csp-relato',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Antes do `$next`: a view é montada lá dentro e precisa do valor.
        $nonce = base64_encode(random_bytes(16));
        View::share('cspNonce', $nonce);

        $resposta = $next($request);

        $resposta->headers->set('X-Content-Type-Options', 'nosniff');
        $resposta->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $resposta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $resposta->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // HSTS só sob HTTPS: em HTTP o navegador ignora, e em dev atrapalharia.
        if ($request->isSecure()) {
            $resposta->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $politica = ["script-src 'self' 'nonce-{$nonce}' 'report-sample'", ...self::POLITICA];
        $resposta->headers->set('Content-Security-Policy', implode('; ', $politica));

        return $resposta;
    }
}
