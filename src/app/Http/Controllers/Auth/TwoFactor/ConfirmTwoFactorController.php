<?php

namespace App\Http\Controllers\Auth\TwoFactor;

use App\Http\Controllers\Controller;
use App\Services\Auth\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Fase 2 da ativação: valida um código gerado pelo aplicativo e só então liga
 * o segundo fator, devolvendo os códigos de recuperação UMA única vez. No
 * banco eles ficam hasheados, então não há como reexibir depois — mesma
 * escolha do Sócrates e do USI.
 */
class ConfirmTwoFactorController extends Controller
{
    private const CODIGOS_DE_RECUPERACAO = 8;

    public function __invoke(Request $request, TotpService $totp): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $usuario = $request->user();

        if (! $usuario->dois_fatores_segredo) {
            return back()->withErrors(['code' => 'Comece gerando o QR code antes de confirmar.']);
        }

        if ($usuario->doisFatoresAtivo()) {
            return back()->withErrors(['code' => 'A verificação em duas etapas já está ativa nesta conta.']);
        }

        if (! $totp->verify($usuario->dois_fatores_segredo, $request->string('code')->toString())) {
            throw ValidationException::withMessages([
                'code' => 'Código inválido. Confira o horário do celular e tente o código atual.',
            ]);
        }

        $codigos = collect(range(1, self::CODIGOS_DE_RECUPERACAO))
            ->map(fn () => Str::lower(Str::random(4).'-'.Str::random(4)))
            ->all();

        $usuario->forceFill([
            'dois_fatores_confirmado_em' => now(),
            'dois_fatores_codigos_recuperacao' => array_map(fn ($codigo) => Hash::make($codigo), $codigos),
        ])->save();

        // Texto puro só nesta resposta — no banco só existe o hash.
        return back()->with('two_factor_recovery_codes', $codigos);
    }
}
