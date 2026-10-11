<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;
use App\Support\Captcha\Altcha;

Route::get('/', function () {
    // Quem chega pelo botão "quero com os meus dados" de uma demonstração
    // encontra o recado começado. A frase sai do config, pelo slug: o que vem
    // no endereço só ESCOLHE qual, nunca vira texto na página.
    $demo = config('demonstracoes.'.preg_replace('/[^a-z0-9-]/', '', (string) request()->query('demo')));

    return view('welcome', [
        'demos' => config('demonstracoes'),
        'recadoDaDemo' => is_array($demo) ? 'Vi '.$demo['assunto'].' no site e quero algo parecido com os dados do meu negócio. ' : '',
    ]);
});

Route::get('/como-trabalhamos', fn () => view('como-trabalhamos'));

// `throttle` não é sobre o duplo clique (isso é assunto do
// ContactController, que descarta recado idêntico): é o teto para quem
// resolver martelar o único POST do site. 10 por minuto por IP passa longe
// de qualquer uso humano e ainda assim é um teto.
/*
 * Demonstrações: painéis e páginas de exemplo, com dado inventado, para o
 * visitante mexer. A lista e o que cada uma é moram em
 * `config/demonstracoes.php`; slug que não está lá (ou que ainda não está
 * pronta) responde 404 — o endereço nunca escolhe um arquivo de view.
 */
Route::get('/demonstracoes', fn () => view('demonstracoes.index', ['demos' => config('demonstracoes')]));
Route::get('/demonstracoes/{slug}', function (string $slug) {
    $demo = config('demonstracoes.'.$slug);

    abort_unless(is_array($demo) && ($demo['pronta'] ?? false) && view()->exists('demonstracoes.'.$slug), 404);

    return view('demonstracoes.'.$slug, ['slug' => $slug, 'demo' => $demo]);
})->where('slug', '[a-z0-9-]+');

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
