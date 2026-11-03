<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Segundo fator obrigatório para o perfil administrador — decisão do Ricardo
 * em 10/10/2026. É a conta que cria e apaga as outras, muda o site e lê a
 * trilha de auditoria.
 *
 * Quem tem de ativar e ainda não ativou é levado para Minha conta, e as outras
 * telas ficam fechadas até lá. Não desloga: a pessoa precisa estar dentro para
 * ler o QR code.
 *
 * Só olha rota que já pede login. O que é público (o site, o manifesto do
 * PWA) segue respondendo a quem está logado. Mesmo arquivo do BHD.
 */
class ExigirSegundoFator
{
    /** O que continua aberto: a tela que resolve, sair, e o que ela usa. */
    private const LIVRES = [
        'profile.*',
        'two-factor.*',
        'password.*',
        'verification.*',
        'logout',
        'reportar-erro',
        'feedback-de-tela',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario === null
            || ! config('auth.dois_fatores_obrigatorio')
            || $usuario->doisFatoresAtivo()
            || ! $usuario->exigeDoisFatores()
            || ! in_array('auth', $request->route()?->gatherMiddleware() ?? [], true)
            || $request->routeIs(self::LIVRES)) {
            return $next($request);
        }

        return redirect()->route('profile.edit');
    }
}
