<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeçalhos de segurança do site — até 29/09/2026 ele não mandava nenhum
 * (pendência 134), e os produtos da casa mandam todos.
 *
 * Sem Content-Security-Policy DE PROPÓSITO, por enquanto: o site carrega
 * fontes, analytics e scripts de terceiros, e uma política escrita sem medir
 * quebra a página em produção sem aviso. A CSP entra depois de levantar as
 * origens reais (modo report-only primeiro), como foi feito nos produtos.
 */
class SecurityHeaders
{
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

        return $resposta;
    }
}
