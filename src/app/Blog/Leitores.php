<?php

declare(strict_types=1);

namespace App\Blog;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use UrbanoDev\Blog\Blog;
use UrbanoDev\Blog\Post;

/**
 * Contagem de leitura do blog (bloco 3b da 246, 04/11/2026).
 *
 * Feita aqui, sem script de terceiro: o navegador manda um `sendBeacon` quando
 * a página abre, e o servidor soma 1 no agregado do dia. Não guarda IP, sessão
 * nem aparelho. Robô quase nunca roda JS; o que roda e se anuncia no
 * User-Agent é descartado aqui. A sessão (que o site já tem) evita que o mesmo
 * leitor conte duas vezes o mesmo texto.
 *
 * Banco fora não derruba página: contar vira no-op e o "mais lidos" some.
 */
class Leitores
{
    /** Quem se anuncia como robô, prévia de link ou ferramenta de linha de comando. */
    private const ROBO = '/bot|crawl|spider|slurp|preview|facebookexternalhit|embedly|headless|lighthouse|pingdom|monitor|curl|wget|python|httpclient|okhttp|go-http|java\//i';

    public function __construct(private readonly Blog $blog) {}

    public static function ehRobo(?string $userAgent): bool
    {
        return $userAgent === null || trim($userAgent) === '' || preg_match(self::ROBO, $userAgent) === 1;
    }

    public function registrarLeitura(string $slug): bool
    {
        if ($this->blog->achar($slug) === null) {
            return false;
        }

        try {
            DB::table('blog_leituras')->upsert(
                [['slug' => $slug, 'dia' => now()->toDateString(), 'total' => 1]],
                ['slug', 'dia'],
                ['total' => DB::raw('blog_leituras.total + 1')],
            );

            return true;
        } catch (Throwable $e) {
            Log::warning('Leitura do blog não contada', ['slug' => $slug, 'erro' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Comentários aprovados do texto, do mais antigo ao mais novo (conversa se
     * lê de cima para baixo). Null com o banco fora: a página mostra o texto e
     * esconde o bloco, em vez de cair.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\BlogComentario>|null
     */
    public function comentariosAprovados(string $slug): ?\Illuminate\Support\Collection
    {
        try {
            return \App\Models\BlogComentario::query()->aprovados()->where('slug', $slug)->orderBy('created_at')->limit(200)->get();
        } catch (Throwable $e) {
            Log::warning('Comentários do blog indisponíveis', ['slug' => $slug, 'erro' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Os mais lidos ainda publicados, em `$dias` dias. Guardado 10 minutos:
     * a lista pública não precisa ser ao vivo, e o banco não é consultado a
     * cada visita da página do blog.
     *
     * @return list<array{post: Post, leituras: int}>
     */
    public function maisLidos(int $dias = 30, int $quantos = 5): array
    {
        try {
            $contagem = Cache::remember("blog:mais-lidos:{$dias}", 600, fn () => DB::table('blog_leituras')
                ->where('dia', '>=', now()->subDays($dias - 1)->toDateString())
                ->groupBy('slug')
                ->selectRaw('slug, SUM(total) AS leituras')
                ->orderByDesc('leituras')
                ->limit(50)
                ->pluck('leituras', 'slug')
                ->map(fn ($n) => (int) $n)
                ->all());
        } catch (Throwable $e) {
            Log::warning('Mais lidos do blog indisponível', ['erro' => $e->getMessage()]);

            return [];
        }

        $lista = [];
        foreach ($contagem as $slug => $leituras) {
            $post = $this->blog->achar((string) $slug);
            if ($post !== null) {
                $lista[] = ['post' => $post, 'leituras' => $leituras];
            }
            if (count($lista) === $quantos) {
                break;
            }
        }

        return $lista;
    }
}
