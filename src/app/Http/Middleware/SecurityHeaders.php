<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeçalhos de segurança do site — até 29/09/2026 ele não mandava nenhum
 * (pendência 134), e os produtos da casa mandam todos.
 *
 * A CSP está em REPORT-ONLY desde 18/10/2026 (pendência 136): o navegador
 * não barra nada, só conta ao `POST /csp-relato` o que teria barrado, e o
 * `CspRelatoController` guarda a lista em `storage/logs/csp-*.log`. Uma
 * política escrita sem medir quebra a página em produção sem aviso.
 *
 * A política abaixo é a que o site QUER ter, não a que ele aguenta hoje. Já se
 * sabe o que ela vai acusar: os `<script>` embutidos (tema, menu,
 * acessibilidade, progresso, home) e os `onclick=` do menu e do modal. Esses
 * saem para arquivo ou ganham hash antes da troca para
 * `Content-Security-Policy` — o log diz se sobrou mais alguma coisa.
 *
 *  - `'report-sample'`: o relato traz os primeiros 40 caracteres do script
 *    barrado — é o que diz QUAL script embutido é, sem depender da linha.
 *  - `fonts.bunny.net`: as três famílias do layout (CSS e arquivos de fonte).
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
        "script-src 'self' 'report-sample'",
        "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
        "font-src 'self' https://fonts.bunny.net",
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
        $resposta = $next($request);

        $resposta->headers->set('X-Content-Type-Options', 'nosniff');
        $resposta->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $resposta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $resposta->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // HSTS só sob HTTPS: em HTTP o navegador ignora, e em dev atrapalharia.
        if ($request->isSecure()) {
            $resposta->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $resposta->headers->set('Content-Security-Policy-Report-Only', implode('; ', self::POLITICA));

        return $resposta;
    }
}
