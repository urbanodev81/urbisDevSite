<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Lancamento;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * O banner de lançamentos logo depois do hero (bloco 4c da 246, 06/11/2026).
 *
 * Até aqui era `config/lancamentos.php` + capturas em `public/lancamentos/`.
 * Agora é a tabela `lancamentos`, editada em Site › Lançamentos, e a home lê
 * só `lista()`.
 *
 * **Banco fora → `PADRAO`** (os 2 slides de 06/11), sem guardar cache, para a
 * próxima requisição tentar de novo (`SitePublicoSemBancoTest`). Nenhum ativo
 * é escolha de quem editou: o banner some da home.
 *
 * `imagem` guarda um de dois formatos: caminho no disco `public` (`site/…jpg`,
 * o que a tela sobe, recortado em 1200×750 por `ImagemDoSite`) ou endereço do
 * próprio site começando por `/` (as duas capturas de sempre, que seguem em
 * `public/lancamentos/`). `url()` resolve os dois.
 */
final class LancamentosDoSite
{
    private const CHAVE_CACHE = 'lancamentos.site';

    /** Medida do slide; a tela recorta o centro nessa proporção. */
    public const MEDIDA = [1200, 750];

    private const CAMPOS = ['etiqueta', 'titulo', 'texto', 'url', 'chamada', 'secundario_url', 'secundario', 'nota', 'imagem'];

    /** @var list<array<string, mixed>>|null memo da requisição */
    private static ?array $memo = null;

    /**
     * Os 2 slides de 06/11/2026, palavra por palavra (eram o `config/lancamentos.php`).
     * É a semente da migration e a reserva com o banco fora.
     *
     * @var list<array<string, string|null>>
     */
    public const PADRAO = [
        [
            'etiqueta' => 'Nosso produto · No ar',
            'titulo' => 'USI, a plataforma da UrbisDev para imobiliárias.',
            'texto' => 'Site com busca de imóveis, fichas de lançamento e blog, e a gestão por trás, com o acervo, os contatos dos interessados e a marca da imobiliária em tudo.',
            'url' => 'https://usi.urbisdev.tech',
            'chamada' => 'Conhecer o USI',
            'secundario_url' => 'https://usi.urbisdev.tech/planos',
            'secundario' => 'Ver planos e preços',
            'nota' => '30 dias grátis, sem cartão de crédito.',
            'imagem' => '/lancamentos/usi.jpg',
        ],
        [
            'etiqueta' => 'Cliente · No ar',
            'titulo' => 'Imóveis Jacob, um site feito com o USI.',
            'texto' => 'Busca por negócio, tipo, cidade e faixa de preço, ficha de cada imóvel e contato direto com o corretor, tudo ligado ao sistema da imobiliária.',
            'url' => 'https://imoveisjacob.com.br',
            'chamada' => 'Visitar o site',
            'secundario_url' => 'https://usi.urbisdev.tech',
            'secundario' => 'Conhecer o USI',
            'nota' => null,
            'imagem' => '/lancamentos/imoveis-jacob.jpg',
        ],
    ];

    /** @return list<array<string, mixed>> os lançamentos ativos, na ordem, com `imagem_url` pronto */
    public static function lista(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }

        try {
            $linhas = Cache::rememberForever(self::CHAVE_CACHE, fn () => Lancamento::query()
                ->where('ativo', true)->orderBy('ordem')->orderBy('id')
                ->get(self::CAMPOS)
                ->map(fn (Lancamento $l) => $l->only(self::CAMPOS))
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

    /** Endereço público da imagem gravada (disco `public` ou arquivo de `public/`). */
    public static function url(?string $imagem): ?string
    {
        if (! $imagem) {
            return null;
        }

        return str_starts_with($imagem, '/') ? $imagem : Storage::disk('public')->url($imagem);
    }

    /** Apaga do disco uma imagem que a tela subiu; as de `public/lancamentos/` ficam. */
    public static function apagarImagem(?string $imagem): void
    {
        if ($imagem && str_starts_with($imagem, ConfiguracaoDoSite::PASTA_IMAGENS.'/')) {
            Storage::disk('public')->delete($imagem);
        }
    }

    /** @param array<string, mixed> $l */
    private static function preparar(array $l): array
    {
        return [...$l, 'imagem_url' => self::url($l['imagem'] ?? null)];
    }
}
