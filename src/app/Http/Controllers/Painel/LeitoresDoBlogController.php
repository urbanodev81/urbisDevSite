<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\BlogComentario;
use App\Models\BlogVoto;
use App\Models\Contato;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use UrbanoDev\Blog\Blog;
use UrbanoDev\Blog\Post;

/**
 * O que os leitores fazem com cada texto (04/11/2026, bloco 3b da 246):
 * leituras (contagem nossa, sem script de terceiro), "ajudou?", comentários e
 * contatos que chegaram vindo do texto. Uma linha por texto publicado.
 */
class LeitoresDoBlogController extends Controller
{
    public function __invoke(Blog $blog): Response
    {
        $hoje = now()->toDateString();
        $leituras = DB::table('blog_leituras')
            ->groupBy('slug')
            ->selectRaw('slug, SUM(total) AS total')
            ->selectRaw('SUM(CASE WHEN dia >= ? THEN total ELSE 0 END) AS sete', [now()->subDays(6)->toDateString()])
            ->selectRaw('SUM(CASE WHEN dia >= ? THEN total ELSE 0 END) AS trinta', [now()->subDays(29)->toDateString()])
            ->get()->keyBy('slug');
        $votos = BlogVoto::query()->groupBy('slug')
            ->selectRaw('slug, SUM(CASE WHEN util THEN 1 ELSE 0 END) AS sim, SUM(CASE WHEN util THEN 0 ELSE 1 END) AS nao')
            ->get()->keyBy('slug');
        $comentarios = BlogComentario::query()->groupBy('slug')
            ->selectRaw("slug, SUM(CASE WHEN status = 'aprovado' THEN 1 ELSE 0 END) AS aprovados, SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) AS pendentes")
            ->get()->keyBy('slug');
        $contatos = Contato::withTrashed()->whereNotNull('texto')->groupBy('texto')
            ->selectRaw('texto, COUNT(*) AS total')->pluck('total', 'texto');

        $posts = $blog->publicados();
        $titulos = collect($posts)->mapWithKeys(fn (Post $p) => [$p->slug => $p->titulo]);

        $linhas = array_map(function (Post $p) use ($leituras, $votos, $comentarios, $contatos) {
            $l = $leituras[$p->slug] ?? null;
            $v = $votos[$p->slug] ?? null;
            $c = $comentarios[$p->slug] ?? null;
            $sim = (int) ($v->sim ?? 0);
            $nao = (int) ($v->nao ?? 0);

            return [
                'slug' => $p->slug,
                'titulo' => $p->titulo,
                'data' => $p->data->format('d/m/Y'),
                'url' => url('/blog/'.$p->slug),
                'leituras' => ['sete' => (int) ($l->sete ?? 0), 'trinta' => (int) ($l->trinta ?? 0), 'total' => (int) ($l->total ?? 0)],
                'votos' => ['sim' => $sim, 'nao' => $nao, 'pct' => $sim + $nao > 0 ? (int) round(100 * $sim / ($sim + $nao)) : null],
                'comentarios' => ['aprovados' => (int) ($c->aprovados ?? 0), 'pendentes' => (int) ($c->pendentes ?? 0)],
                'contatos' => (int) ($contatos[$p->slug] ?? 0),
            ];
        }, $posts);

        // Mais lido em 30 dias primeiro; empate, o mais novo.
        usort($linhas, fn ($a, $b) => $b['leituras']['trinta'] <=> $a['leituras']['trinta']);

        return Inertia::render('Painel/Blog/Leitores', [
            'textos' => $linhas,
            'faltou' => BlogVoto::query()->whereNotNull('faltou')->latest('created_at')->limit(20)->get()
                ->map(fn (BlogVoto $v) => [
                    'id' => $v->id,
                    'titulo' => $titulos[$v->slug] ?? $v->slug,
                    'texto' => $v->faltou,
                    'quando' => $v->created_at->format('d/m/Y'),
                ])->all(),
            'totais' => [
                'trinta' => array_sum(array_map(fn ($l) => $l['leituras']['trinta'], $linhas)),
                // Só os publicados, como as linhas: agendado lido pela prévia não entra.
                'hoje' => (int) DB::table('blog_leituras')->where('dia', $hoje)->whereIn('slug', $titulos->keys()->all())->sum('total'),
            ],
        ]);
    }
}
