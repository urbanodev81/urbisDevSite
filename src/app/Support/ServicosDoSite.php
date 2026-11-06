<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Servico;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * A lista de "O que fazemos" (bloco 4b da pendência 246, 06/11/2026).
 *
 * Os 4 cartões da home estavam escritos três vezes, com redações diferentes:
 * no `welcome`, no `OfferCatalog` do `partials/seo` e no `seo/llms`. Agora as
 * três saídas leem `lista()`, e quem mexe é a tela Site › Serviços.
 *
 * **Banco fora → `PADRAO`** (os 4 de 06/11), sem guardar cache, para a próxima
 * requisição tentar de novo. O site público não cai porque o Postgres caiu
 * (`SitePublicoSemBancoTest`). Tabela sem nenhum serviço ativo é escolha de
 * quem editou: a seção some da home e o catálogo some do JSON-LD.
 *
 * `icone` e `cor` são chaves de listas FECHADAS: o banco não guarda SVG nem
 * CSS, então nada que vem da tela vira marcação.
 */
final class ServicosDoSite
{
    private const CHAVE_CACHE = 'servicos.site';

    /** @var list<array<string, mixed>>|null memo da requisição */
    private static ?array $memo = null;

    /** Desenhos (Lucide, traço 2, 24×24): rótulo para a tela e o miolo do `<svg>`. */
    public const ICONES = [
        'predio' => ['Prédio', '<path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-4h6v4"/><path d="M9 10h.01"/><path d="M15 10h.01"/><path d="M9 14h.01"/><path d="M15 14h.01"/>'],
        'painel' => ['Painel', '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18"/><path d="M8 14h4"/><path d="M8 17h8"/>'],
        'coracao' => ['Coração', '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>'],
        'pessoas' => ['Pessoas', '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
        'calendario' => ['Calendário', '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/>'],
        'carrinho' => ['Carrinho', '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>'],
        'livro' => ['Livro', '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>'],
        'ferramenta' => ['Ferramenta', '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>'],
        'grafico' => ['Gráfico', '<path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>'],
        'escudo' => ['Escudo', '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>'],
    ];

    /** Cor da faixa e do ícone: só tokens de `partials/tokens.blade.php`, que já têm par claro/escuro. */
    public const CORES = [
        'ambar' => ['Âmbar', 'var(--amber-500)'],
        'roxo' => ['Roxo', 'var(--d-urb)'],
        'verde' => ['Verde-água', 'var(--d-synapse)'],
        'vermelho' => ['Vermelho', 'var(--d-ssb)'],
    ];

    public const SITUACOES = [
        'no_ar' => 'No ar',
        'em_construcao' => 'Em construção',
    ];

    /**
     * Os 4 cartões de 06/11/2026, palavra por palavra. É a semente da migration
     * e a reserva com o banco fora.
     *
     * @var list<array<string, string|null>>
     */
    public const PADRAO = [
        [
            'titulo' => 'Imobiliárias', 'frente' => 'Imobiliário', 'icone' => 'predio', 'cor' => 'roxo', 'situacao' => 'no_ar',
            'texto' => 'O USI é o nosso sistema imobiliário: site com busca de imóveis, fichas de lançamento e blog, e a gestão por trás, com cadastro do acervo, contatos dos interessados e a sua marca em tudo.',
            'link_url' => 'https://usi.urbisdev.tech', 'link_rotulo' => 'Conhecer o USI',
            'nome_no_catalogo' => 'USI: site e sistema de gestão para imobiliárias',
        ],
        [
            'titulo' => 'Sites que você mesmo atualiza', 'frente' => 'Sites com painel', 'icone' => 'painel', 'cor' => 'ambar', 'situacao' => 'no_ar',
            'texto' => 'Para bandas, artistas, oficinas e profissionais: agenda, fotos, vídeos e textos trocados pelo painel, sem depender de ninguém, com o site rápido e acessível.',
            'link_url' => null, 'link_rotulo' => null,
            'nome_no_catalogo' => 'Sites com painel próprio',
        ],
        [
            'titulo' => 'Clínicas de terapia', 'frente' => 'Saúde', 'icone' => 'coracao', 'cor' => 'verde', 'situacao' => 'em_construcao',
            'texto' => 'Gestão da clínica com agenda, evolução de cada paciente e cuidado com o dado de saúde desde o desenho.',
            'link_url' => null, 'link_rotulo' => null,
            'nome_no_catalogo' => 'Sistema de gestão para clínicas de terapia',
        ],
        [
            'titulo' => 'Sindicatos e associações', 'frente' => 'Associações', 'icone' => 'pessoas', 'cor' => 'vermelho', 'situacao' => 'em_construcao',
            'texto' => 'Cadastro de associados, mensalidades, comunicação com a base e votações, num sistema só.',
            'link_url' => null, 'link_rotulo' => null,
            'nome_no_catalogo' => 'Sistema para sindicatos e associações',
        ],
    ];

    /** @return list<array<string, mixed>> os serviços ativos, na ordem, já prontos para a view */
    public static function lista(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }

        try {
            $linhas = Cache::rememberForever(self::CHAVE_CACHE, fn () => Servico::query()
                ->where('ativo', true)->orderBy('ordem')->orderBy('id')
                ->get(['titulo', 'frente', 'texto', 'icone', 'cor', 'situacao', 'link_url', 'link_rotulo', 'nome_no_catalogo'])
                ->map(fn (Servico $s) => $s->only(['titulo', 'frente', 'texto', 'icone', 'cor', 'situacao', 'link_url', 'link_rotulo', 'nome_no_catalogo']))
                ->all());

            return self::$memo = array_map(self::preparar(...), $linhas);
        } catch (Throwable $e) {
            report($e);

            return array_map(self::preparar(...), self::PADRAO);
        }
    }

    public static function esquecer(): void
    {
        self::$memo = null;
        Cache::forget(self::CHAVE_CACHE);
    }

    /**
     * Os itens do `OfferCatalog` do JSON-LD. A descrição é o texto do cartão;
     * em construção, a frase diz isso (o buscador não vê o selo).
     *
     * @return list<array<string, mixed>>
     */
    public static function paraOCatalogo(): array
    {
        return array_map(fn (array $s) => [
            '@type' => 'Offer',
            'itemOffered' => array_filter([
                '@type' => 'Service',
                'name' => $s['nome_no_catalogo'],
                'description' => $s['texto'].($s['situacao'] === 'em_construcao' ? ' Em construção.' : ''),
                'url' => $s['link_url'],
            ]),
        ], self::lista());
    }

    /** O miolo do `<svg>` de um ícone da lista; chave desconhecida cai no painel. */
    public static function svg(string $icone): string
    {
        return (self::ICONES[$icone] ?? self::ICONES['painel'])[1];
    }

    /** Opções para a tela do painel (ícone com o desenho, para a pré-visualização). */
    public static function opcoes(): array
    {
        $lista = fn (array $origem, callable $item) => collect($origem)->map($item)->values()->all();

        return [
            'icones' => $lista(self::ICONES, fn ($v, $k) => ['valor' => $k, 'rotulo' => $v[0], 'svg' => $v[1]]),
            'cores' => $lista(self::CORES, fn ($v, $k) => ['valor' => $k, 'rotulo' => $v[0], 'css' => $v[1]]),
            'situacoes' => $lista(self::SITUACOES, fn ($v, $k) => ['valor' => $k, 'rotulo' => $v]),
        ];
    }

    /** @param array<string, mixed> $s */
    private static function preparar(array $s): array
    {
        return [
            ...$s,
            'nome_no_catalogo' => ($s['nome_no_catalogo'] ?? null) ?: $s['titulo'],
            'cor_css' => (self::CORES[$s['cor']] ?? self::CORES['ambar'])[1],
            'svg' => self::svg((string) $s['icone']),
            'situacao_rotulo' => self::SITUACOES[$s['situacao']] ?? self::SITUACOES['no_ar'],
        ];
    }
}
