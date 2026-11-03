<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\Captcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            // Cada envio manda e-mail para uma caixa de terceiro: sem captcha
            // o formulário servia de disparador de spam — pendência 134.
            'captcha_token' => Captcha::rules(),
        ]);

        // Resposta neutra para não revelar se o e-mail existe — pendência 107.
        // INVALID_USER virava erro no campo e RESET_THROTTLED também entrega
        // (o cooldown só existe para quem tem conta): os dois respondem igual
        // ao envio. É o que o SSB e o USI já faziam.
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'Se o e-mail estiver cadastrado, enviamos o link de redefinição.');
    }
}
