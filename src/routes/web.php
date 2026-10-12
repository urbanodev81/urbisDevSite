<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DemoLeadController;
use App\Support\Captcha\Altcha;
use App\Support\Portfolio;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    // Quem chega pelo botão "quero com os meus dados" de uma demonstração
    // encontra o recado começado. A frase sai do config, pelo slug: o que vem
    // no endereço só ESCOLHE qual, nunca vira texto na página.
    $demo = config('demonstracoes.'.preg_replace('/[^a-z0-9-]/', '', (string) request()->query('demo')));

    return view('welcome', [
        'demos' => config('demonstracoes'),
        'trabalhos' => app(Portfolio::class)->porCategoria(),
        'recadoDaDemo' => is_array($demo) ? 'Vi '.$demo['assunto'].' no site e quero algo parecido com os dados do meu negócio. ' : '',
    ]);
});

Route::get('/como-trabalhamos', fn () => view('como-trabalhamos'));

/*
 * Trabalhos: o que a casa já fez, por categoria. A lista vem do Sócrates
 * (`App\Support\Portfolio`), que decide o que aparece e com que nome. Sem
 * nenhum trabalho marcado lá — ou sem `PORTFOLIO_URL` — a página não existe.
 */
Route::get('/trabalhos', function (Portfolio $portfolio) {
    abort_unless($portfolio->temItens(), 404);

    return view('trabalhos', ['categorias' => $portfolio->porCategoria()]);
});

// A cópia local das capturas. O nome é o que o Sócrates gerou (20 caracteres
// ao acaso) e muda a cada troca de imagem: pode ficar guardado para sempre.
Route::get('/trabalhos/capturas/{arquivo}', function (string $arquivo) {
    $caminho = Portfolio::caminhoDaCaptura($arquivo);
    abort_unless($caminho !== null && Storage::disk('local')->exists($caminho), 404);

    return Storage::disk('local')->response($caminho, null, [
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->where('arquivo', '[a-z0-9]{20}\\.(png|jpg|jpeg|webp)');

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

    return view('demonstracoes.'.$slug, [
        'slug' => $slug,
        'demo' => $demo,
        // Demonstração atrás do formulário de lead: a sessão diz se este
        // visitante já passou por ele (`DemoLeadController`).
        'liberada' => ! ($demo['pede_contato'] ?? false) || in_array($slug, (array) session('demos_liberadas', []), true),
    ]);
})->where('slug', '[a-z0-9-]+');

// O formulário que libera uma demonstração com `pede_contato`. Mesmo teto do
// contato: é POST público que manda e-mail.
Route::post('/demonstracoes/{slug}/liberar', [DemoLeadController::class, 'store'])
    ->where('slug', '[a-z0-9-]+')
    ->middleware('throttle:10,1');

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
