<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Blog\LeitoresController;
use App\Http\Controllers\Blog\LinkedinController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Painel\ComentarioController;
use App\Http\Controllers\Painel\LeitoresDoBlogController;
use App\Http\Controllers\CspRelatoController;
use App\Http\Controllers\DemoDadosController;
use App\Http\Controllers\DemoIaController;
use App\Http\Controllers\DemoLeadController;
use App\Http\Controllers\Painel\AuditoriaController;
use App\Http\Controllers\Painel\BlogPreviaController;
use App\Http\Controllers\Painel\TextoController;
use App\Http\Controllers\Painel\ContatoController;
use App\Http\Controllers\Painel\ConfiguracaoController;
use App\Http\Controllers\Painel\PainelController;
use App\Http\Controllers\Painel\UsuarioController;
use App\Http\Controllers\ProfileController;
use App\Support\Captcha\Altcha;
use App\Support\PaginasPublicas;
use App\Support\PergunteAosDados;
use App\Support\Portfolio;
use UrbanoDev\Blog\Blog;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    // Quem chega pelo botão "quero com os meus dados" de uma demonstração
    // encontra o recado começado. A frase sai do config, pelo slug: o que vem
    // no endereço só ESCOLHE qual, nunca vira texto na página.
    $demo = config('demonstracoes.'.preg_replace('/[^a-z0-9-]/', '', (string) request()->query('demo')));

    // O mesmo vale para quem chega de um texto do blog: o slug só escolhe um
    // texto da lista. `?guia=` é o nome antigo (até 03/11/2026), ainda aceito.
    $slugTexto = request()->query('texto', request()->query('guia'));
    $texto = app(Blog::class)->achar(preg_replace('/[^a-z0-9-]/', '', is_string($slugTexto) ? $slugTexto : ''));

    return view('welcome', [
        'demos' => config('demonstracoes'),
        'trabalhos' => app(Portfolio::class)->porCategoria(),
        // O mesmo slug vai escondido no formulário: o painel mostra de onde o
        // contato veio (04/11/2026, bloco 2 da 246).
        'deDemo' => is_array($demo) ? preg_replace('/[^a-z0-9-]/', '', (string) request()->query('demo')) : null,
        'deTexto' => $texto?->slug,
        'recadoDaDemo' => match (true) {
            is_array($demo) => 'Vi '.$demo['assunto'].' no site e quero algo parecido com os dados do meu negócio. ',
            $texto !== null => 'Li o texto "'.$texto->titulo.'" no blog e quero conversar sobre o meu caso. ',
            default => '',
        },
    ]);
});

Route::get('/como-trabalhamos', fn () => view('como-trabalhamos'));
Route::get('/privacidade', fn () => view('privacidade'));

// "Guias" virou "Blog" em 03/11/2026 (pendência 247). Link antigo, de busca ou
// já compartilhado, cai no mesmo lugar do endereço novo, com 301 e a query junto.
Route::get('/guias/{resto?}', function (?string $resto = null) {
    $query = request()->getQueryString();

    return redirect('/blog'.($resto === null ? '' : '/'.$resto).($query ? '?'.$query : ''), 301);
})->where('resto', '[a-z0-9/-]+');

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

// SEO e GEO (18/10/2026): a lista vem de `App\Support\PaginasPublicas`.
Route::get('/sitemap.xml', fn (PaginasPublicas $paginas) => response()
    ->view('seo.sitemap', ['paginas' => array_keys($paginas->todas()), 'datas' => $paginas->ultimasMudancas()])
    ->header('Content-Type', 'application/xml; charset=UTF-8'));

// Para assistentes de IA (a convenção de llmstxt.org): quem é a casa, o que
// faz e onde está cada coisa, em texto corrido. Mesmo conteúdo do JSON-LD do
// `partials/seo`, sem nome de produto nem de cliente.
Route::get('/llms.txt', fn (PaginasPublicas $paginas) => response()
    ->view('seo.llms', ['paginas' => $paginas->todas()])
    ->header('Content-Type', 'text/plain; charset=UTF-8'));

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
        // Só a demonstração de IA mostra a tabela que manda ao modelo.
        'dados' => $slug === PergunteAosDados::DEMO ? PergunteAosDados::dados() : null,
    ]);
})->where('slug', '[a-z0-9-]+');

// Dado público para as demonstrações (20/10/2026): CEP, CNPJ e feriados, pelo
// servidor e com cache (`App\Support\DadosPublicos`). Antes da rota `{slug}`
// não precisa: o padrão dela não aceita barra. Balde próprio no throttle, para
// quem digita CEP não gastar o teto do formulário de contato.
Route::middleware('throttle:30,1,dados')->prefix('/demonstracoes/dados')->group(function () {
    Route::get('/cep/{cep}', [DemoDadosController::class, 'cep'])->where('cep', '[0-9]{8}');
    Route::get('/cnpj/{cnpj}', [DemoDadosController::class, 'cnpj'])->where('cnpj', '[0-9]{14}');
    Route::get('/feriados', [DemoDadosController::class, 'feriados']);
});

// O formulário que libera uma demonstração com `pede_contato`. Mesmo teto do
// contato: é POST público que manda e-mail.
Route::post('/demonstracoes/{slug}/liberar', [DemoLeadController::class, 'store'])
    ->where('slug', '[a-z0-9-]+')
    ->middleware('throttle:10,1');

// A pergunta da demonstração de IA. Cada uma vira chamada de modelo no
// Sócrates, que tem os tetos de verdade (por pessoa e por dia); este aqui é só
// contra o martelo.
Route::post('/demonstracoes/pergunte/perguntar', [DemoIaController::class, 'perguntar'])
    ->middleware('throttle:10,1');

Route::post('/contato', [ContactController::class, 'store'])->middleware('throttle:10,1');

// Leitores do blog (04/11/2026, bloco 3b da 246). Moram aqui e não no pacote
// porque usam banco; as views entram pelos `encaixes` de config/blog.php.
// Cada uma com balde próprio de throttle (o terceiro argumento).
Route::prefix('/blog')->where(['slug' => '[a-z0-9-]+'])->group(function () {
    Route::post('/{slug}/lido', [LeitoresController::class, 'lido'])->middleware('throttle:60,1,blog-lido');
    Route::post('/{slug}/ajudou', [LeitoresController::class, 'ajudou'])->middleware('throttle:20,1,blog-voto');
    Route::post('/{slug}/comentarios', [LeitoresController::class, 'comentar'])->middleware('throttle:5,1,blog-comentario');
    Route::get('/entrar/linkedin', [LinkedinController::class, 'entrar'])->middleware('throttle:10,1,blog-linkedin');
    Route::get('/entrar/linkedin/retorno', [LinkedinController::class, 'retorno'])->middleware('throttle:10,1,blog-linkedin');
    Route::post('/entrar/linkedin/sair', [LinkedinController::class, 'sair']);
});

// Relatos da CSP em report-only (pendência 136). Quem manda é o navegador,
// sem sessão nem token: daí sair do CSRF. O `csp` no fim do throttle dá a
// esta rota um balde próprio — sem ele, uma página com cinco violações
// gastaria o teto do formulário de contato (PADROES § piso, item 10).
Route::post('/csp-relato', CspRelatoController::class)
    ->withoutMiddleware(ValidateCsrfToken::class)
    ->middleware('throttle:60,1,csp');

// O desafio do captcha (Altcha auto-hospedado, 29/09/2026). Público e sem
// sessão: é pedido pelo formulário antes de existir qualquer coisa. O
// `throttle` existe porque emitir desafio custa hash NOSSO e nada do cliente.
// `no-store` porque o desafio é de uso único: resposta guardada em cache
// reapresenta um desafio já queimado e o visitante leva uma recusa injusta.
Route::get('/captcha/desafio', fn (Altcha $captcha) => response()
    ->json($captcha->criarDesafio())
    ->header('Cache-Control', 'no-store, private'))
    ->middleware('throttle:30,1');

/*
|--------------------------------------------------------------------------
| Painel (03/11/2026, pendência 246)
|--------------------------------------------------------------------------
|
| Tudo sob `/painel`, em Inertia; o site público acima continua Blade e não
| depende do banco. Login, 2º fator e senha moram em `routes/auth.php`, com o
| mesmo prefixo. O 2º fator é obrigatório para TODA conta
| (`ExigirSegundoFator` + `User::exigeDoisFatores`): sem ele confirmado, toda
| tela do painel leva ao Perfil.
|
| ⚠️ `ver` × `gerenciar`: a de leitura abre a tela, a de escrita muda o dado.
| O menu esconde o que a pessoa não pode abrir, mas quem decide é o `can:`
| de cada rota (PermissoesPorRotaTest).
*/
Route::middleware('auth')->prefix('painel')->group(function () {
    Route::get('/', [PainelController::class, 'index'])->name('painel.inicio');

    // Contatos (04/11/2026, bloco 2): quem escreveu ou abriu uma demonstração.
    Route::middleware('can:leads.ver')->name('painel.')->group(function () {
        Route::get('contatos', [ContatoController::class, 'index'])->name('contatos.index');
        Route::get('contatos/{contato}', [ContatoController::class, 'show'])->name('contatos.show');
    });
    Route::middleware('can:leads.gerenciar')->name('painel.')->group(function () {
        Route::put('contatos/{contato}', [ContatoController::class, 'update'])->name('contatos.update');
        Route::delete('contatos/{contato}', [ContatoController::class, 'destroy'])->name('contatos.destroy');
        Route::patch('contatos/{contato}/restaurar', [ContatoController::class, 'restaurar'])->name('contatos.restaurar');
        Route::get('contatos/{contato}/exportar', [ContatoController::class, 'exportar'])
            ->middleware('throttle:20,1,exportar-titular')->name('contatos.exportar');
    });

    // Prévia de texto agendado (04/11/2026): lê o futuro com a cara do site.
    Route::get('blog/previa/{slug}', BlogPreviaController::class)
        ->middleware('can:blog.gerenciar')->where('slug', '[a-z0-9-]+')->name('painel.blog.previa');

    // Blog no banco (04/11/2026, bloco 3a): escrever, agendar, arquivar.
    Route::middleware('can:blog.gerenciar')->prefix('blog')->name('painel.blog.textos.')->group(function () {
        Route::get('textos', [TextoController::class, 'index'])->name('index');
        Route::get('textos/novo', [TextoController::class, 'create'])->name('create');
        Route::post('textos', [TextoController::class, 'store'])->name('store');
        Route::get('textos/{texto}', [TextoController::class, 'edit'])->name('edit');
        Route::put('textos/{texto}', [TextoController::class, 'update'])->name('update');
        Route::delete('textos/{texto}', [TextoController::class, 'destroy'])->name('destroy');
        Route::patch('textos/{texto}/restaurar', [TextoController::class, 'restaurar'])->name('restaurar');
        Route::get('textos/{texto}/previa', [BlogPreviaController::class, 'doBanco'])->name('previa');
        Route::get('imagens', [TextoController::class, 'imagens'])->name('imagens');
        Route::post('imagens', [TextoController::class, 'imagem'])->middleware('throttle:30,1')->name('imagem');
    });

    // Leitores (04/11/2026, bloco 3b): moderar comentários e ver o que foi lido.
    Route::middleware('can:blog.gerenciar')->prefix('blog')->name('painel.blog.')->group(function () {
        Route::get('comentarios', [ComentarioController::class, 'index'])->name('comentarios.index');
        Route::put('comentarios/{comentario}', [ComentarioController::class, 'update'])->name('comentarios.update');
        Route::delete('comentarios/{comentario}', [ComentarioController::class, 'destroy'])->name('comentarios.destroy');
        Route::patch('comentarios/{comentario}/restaurar', [ComentarioController::class, 'restaurar'])->name('comentarios.restaurar');
        Route::get('leitores', LeitoresDoBlogController::class)->name('leitores.index');
    });

    Route::get('usuarios', [UsuarioController::class, 'index'])
        ->middleware('can:usuarios.ver')->name('painel.usuarios.index');
    Route::middleware('can:usuarios.gerenciar')->name('painel.')->group(function () {
        Route::get('usuarios/criar/novo', [UsuarioController::class, 'create'])->name('usuarios.create');
        Route::post('usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::get('usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
        Route::put('usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::delete('usuarios/{usuario}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');
        Route::patch('usuarios/{usuario}/restaurar', [UsuarioController::class, 'restaurar'])
            ->whereUuid('usuario')->name('usuarios.restaurar');
        Route::delete('usuarios/{usuario}/dois-fatores', [UsuarioController::class, 'desligarSegundoFator'])
            ->middleware('throttle:6,1,desligar-2fa')->name('usuarios.dois-fatores.desligar');
    });

    // Configuração do site (bloco 4a, 06/11/2026). Só o administrador.
    Route::middleware('can:configuracoes.gerenciar')->prefix('site')->name('painel.configuracoes.')->group(function () {
        Route::get('configuracoes', [ConfiguracaoController::class, 'edit'])->name('edit');
        Route::put('configuracoes', [ConfiguracaoController::class, 'update'])->name('update');
        Route::post('configuracoes/imagem/{chave}', [ConfiguracaoController::class, 'imagem'])
            ->middleware('throttle:20,1')->name('imagem');
    });

    Route::get('auditoria', AuditoriaController::class)
        ->middleware('can:auditoria.ver')->name('painel.auditoria.index');

    Route::get('perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('perfil', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
