<?php

namespace App\Http\Controllers\Auth\TwoFactor;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Desligar o segundo fator rebaixa a segurança da conta, então exige a senha
 * atual — senão uma sessão sequestrada desliga a proteção sem atrito nenhum.
 * Serve também para desistir de uma ativação que ficou pela metade.
 */
class DisableTwoFactorController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        $request->user()->forceFill([
            'dois_fatores_segredo' => null,
            'dois_fatores_codigos_recuperacao' => null,
            'dois_fatores_confirmado_em' => null,
        ])->save();

        return back();
    }
}
