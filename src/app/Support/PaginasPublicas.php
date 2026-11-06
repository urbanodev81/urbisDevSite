<?php

namespace App\Support;

use UrbanoDev\Blog\Blog;

/**
 * As páginas que o site quer ver em buscador e assistente de IA, com o nome
 * de cada uma (18/10/2026). Alimenta `/sitemap.xml` e `/llms.txt`.
 *
 * Sai do mesmo lugar que as próprias páginas (`config/demonstracoes.php` e o
 * portfólio), para não envelhecer sozinha: demonstração desligada some daqui
 * como some de `/demonstracoes`. As cinco fichas estáticas de lançamento
 * ficam de fora — são `noindex`.
 */
class PaginasPublicas
{
    public function __construct(private Portfolio $portfolio, private Blog $blog) {}

    /** @return array<string, string> caminho => "Nome" ou "Nome — resumo" */
    public function todas(): array
    {
        $paginas = ['/' => 'Início', '/demonstracoes' => 'Demonstrações', '/como-trabalhamos' => 'Como trabalhamos', '/privacidade' => 'Política de privacidade'];

        if ($this->portfolio->temItens()) {
            $paginas['/trabalhos'] = 'Trabalhos';
        }

        foreach (config('demonstracoes') as $slug => $demo) {
            if (($demo['pronta'] ?? false) && view()->exists('demonstracoes.'.$slug)) {
                $paginas['/demonstracoes/'.$slug] = $demo['titulo'].' — '.$demo['resumo'];
            }
        }

        // Tag, seção e mês do blog ficam de fora: são `noindex` (partials/seo,
        // auditoria SEO/GEO de 06/11/2026).
        $doBlog = array_filter($this->blog->paginas(), fn (string $caminho) => ! preg_match('#^/blog/(tag/|secao/|\d{4}(/|$))#', $caminho), ARRAY_FILTER_USE_KEY);

        return $paginas + $doBlog;
    }

    /** @return array<string, string> caminho => data da última mudança (AAAA-MM-DD), só onde se sabe */
    public function ultimasMudancas(): array
    {
        $datas = [];
        foreach ($this->blog->publicados() as $post) {
            $datas['/blog/'.$post->slug] = ($post->atualizado ?? $post->data)->toDateString();
        }
        if ($datas !== []) {
            $datas['/blog'] = max($datas);
        }

        return $datas;
    }
}
