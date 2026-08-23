<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;

Route::get('/', fn () => view('welcome'));

Route::get('/como-trabalhamos', fn () => view('como-trabalhamos'));

// `throttle` não é sobre o duplo clique (isso é assunto do
// ContactController, que descarta recado idêntico): é o teto para quem
// resolver martelar o único POST do site. 10 por minuto por IP passa longe
// de qualquer uso humano e ainda assim é um teto.
Route::post('/contato', [ContactController::class, 'store'])->middleware('throttle:10,1');
