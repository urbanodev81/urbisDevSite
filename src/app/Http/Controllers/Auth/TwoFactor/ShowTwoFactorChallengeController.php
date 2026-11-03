<?php

namespace App\Http\Controllers\Auth\TwoFactor;

use App\Http\Controllers\Controller;
use App\Services\Auth\AparelhosConfiaveis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tela do segundo fator. Auto-protegida: sem a marca de sessão deixada pelo
 * LoginRequest (senha já conferida), não há desafio nenhum a mostrar — volta
 * para o login em vez de expor a tela.
 */
class ShowTwoFactorChallengeController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('2fa.user_id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge', [
            // Sugestão de apelido, para a pessoa só ajustar.
            'sugestaoDeApelido' => AparelhosConfiaveis::descrever($request->userAgent()),
            'diasDeConfianca' => AparelhosConfiaveis::DIAS,
        ]);
    }
}
