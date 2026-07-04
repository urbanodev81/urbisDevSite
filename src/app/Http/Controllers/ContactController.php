<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|max:255',
            'phone'         => 'nullable|string|max:15',
            'message'       => 'required|string|min:10|max:5000',
            'consent'       => 'required|accepted',
            'turnstile_token' => 'required|string',
        ]);

        $turnstileResponse = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret'   => config('services.turnstile.secret_key'),
            'response' => $validated['turnstile_token'],
        ]);

        if (! $turnstileResponse->successful() || ! $turnstileResponse->json('success')) {
            return back()->with('error', 'Verificação de segurança falhou. Tente novamente.')->withInput();
        }

        Mail::raw(
            "Nome: {$validated['name']}\n"
            . "E-mail: {$validated['email']}\n"
            . "Telefone: " . ($validated['phone'] ?? 'Não informado') . "\n\n"
            . "Mensagem:\n{$validated['message']}",
            function ($message) use ($validated) {
                $message->to(config('mail.from.address'))
                    ->subject('Contato do site — ' . $validated['name'])
                    ->replyTo($validated['email'], $validated['name']);
            }
        );

        Log::info('Contato recebido', [
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ]);

        return back()->with('success', 'Mensagem enviada com sucesso! Entraremos em contato em breve.');
    }
}
