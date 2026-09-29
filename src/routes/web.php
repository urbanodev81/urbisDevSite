<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;
use App\Support\Captcha\Altcha;

Route::get('/', fn () => view('welcome'));

Route::get('/como-trabalhamos', fn () => view('como-trabalhamos'));

// `throttle` não é sobre o duplo clique (isso é assunto do
// ContactController, que descarta recado idêntico): é o teto para quem
// resolver martelar o único POST do site. 10 por minuto por IP passa longe
// de qualquer uso humano e ainda assim é um teto.
Route::post('/contato', [ContactController::class, 'store'])->middleware('throttle:10,1');

// O desafio do captcha (Altcha auto-hospedado, 29/09/2026). Público e sem
// sessão: é pedido pelo formulário antes de existir qualquer coisa. O
// `throttle` existe porque emitir desafio custa hash NOSSO e nada do cliente.
// `no-store` porque o desafio é de uso único: resposta guardada em cache
// reapresenta um desafio já queimado e o visitante leva uma recusa injusta.
Route::get('/captcha/desafio', fn (Altcha $captcha) => response()
    ->json($captcha->criarDesafio())
    ->header('Cache-Control', 'no-store, private'))
    ->middleware('throttle:30,1');
