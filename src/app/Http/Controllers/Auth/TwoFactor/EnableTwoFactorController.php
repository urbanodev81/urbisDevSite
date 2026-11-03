<?php

namespace App\Http\Controllers\Auth\TwoFactor;

use App\Http\Controllers\Controller;
use App\Services\Auth\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Fase 1 da ativação: gera e guarda o segredo, mas NÃO liga o segundo fator
 * (`dois_fatores_confirmado_em` continua nulo). Só o ConfirmTwoFactorController,
 * depois de validar um código de verdade, ativa — assim ninguém se tranca fora
 * da conta com um QR code que o aplicativo nunca leu.
 */
class EnableTwoFactorController extends Controller
{
    public function __invoke(Request $request, TotpService $totp): RedirectResponse
    {
        $usuario = $request->user();

        if ($usuario->doisFatoresAtivo()) {
            return back()->withErrors(['code' => 'A verificação em duas etapas já está ativa nesta conta.']);
        }

        $usuario->forceFill([
            'dois_fatores_segredo' => $totp->generateSecretKey(),
            'dois_fatores_codigos_recuperacao' => null,
            'dois_fatores_confirmado_em' => null,
        ])->save();

        return back();
    }
}
