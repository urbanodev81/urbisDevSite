<?php

namespace App\Support;

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
    public function __construct(private Portfolio $portfolio) {}

    /** @return array<string, string> caminho => "Nome" ou "Nome — resumo" */
    public function todas(): array
    {
        $paginas = ['/' => 'Início', '/demonstracoes' => 'Demonstrações', '/como-trabalhamos' => 'Como trabalhamos'];

        if ($this->portfolio->temItens()) {
            $paginas['/trabalhos'] = 'Trabalhos';
        }

        foreach (config('demonstracoes') as $slug => $demo) {
            if (($demo['pronta'] ?? false) && view()->exists('demonstracoes.'.$slug)) {
                $paginas['/demonstracoes/'.$slug] = $demo['titulo'].' — '.$demo['resumo'];
            }
        }

        return $paginas;
    }
}
