<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Conta com segundo fator: a senha passou, mas quem autentica é o
        // desafio (marca deixada pelo LoginRequest). Aqui ainda não há sessão
        // autenticada, e a sessão só é regenerada quando houver.
        if ($request->session()->has('2fa.user_id')) {
            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();

        /*
         * Só existe uma casa aqui: o painel.
         *
         * A versão que veio do irmão escolhia entre painel e portal do cliente
         * por permissão — e as duas rotas dele não existem neste produto.
         * Ficou 500 no POST de login enquanto a sessão JÁ tinha sido criada,
         * que é o pior formato de defeito: a pessoa entra e vê um erro.
         * `LoginLevaAoPainelTest` afirma o contrário desde 31/08/2026.
         *
         * `intended()` continua na frente: quem clicou num link e foi parar no
         * login volta para onde queria ir, não para a página inicial.
         */
        return redirect()->intended(route('painel.inicio', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
