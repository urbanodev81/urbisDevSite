<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Os trabalhos que o site mostra, lidos do Sócrates (pendência 203).
 *
 * O site continua sem banco: o que vem de lá fica no cache de arquivo, em duas
 * cópias. A `fresca` vence em `portfolio.minutos` e é a que as páginas usam; a
 * `ultima` não vence e é o que sobra quando o Sócrates não responde — o
 * portfólio envelhece, não some. As capturas são copiadas para o disco daqui
 * pelo mesmo motivo: página com imagem quebrada porque outro sistema caiu é
 * pior do que página sem a seção.
 *
 * Nada do que vem de lá é confiado: cada campo é conferido e cortado, e imagem
 * só é baixada do MESMO endereço do portfólio, com nome no formato que o
 * Sócrates gera. Quem responde em `PORTFOLIO_URL` não escolhe de onde o site
 * baixa arquivo.
 */
class Portfolio
{
    private const NOME_DA_CAPTURA = '/^[a-z0-9]{20}\.(png|jpg|jpeg|webp)$/';

    private const TETO_DA_CAPTURA = 2 * 1024 * 1024;

    /** @var array{categorias: array<int, array>, itens: array<int, array>}|null */
    private ?array $lido = null;

    /** @return array{categorias: array<int, array>, itens: array<int, array>} */
    public function dados(): array
    {
        return $this->lido ??= $this->ler();
    }

    public function temItens(): bool
    {
        return $this->dados()['itens'] !== [];
    }

    /**
     * As categorias que têm trabalho, cada uma com os seus, na ordem do Sócrates.
     *
     * @return array<int, array{chave: string, nome: string, descricao: string, itens: array<int, array>}>
     */
    public function porCategoria(): array
    {
        $dados = $this->dados();
        $saida = [];

        foreach ($dados['categorias'] as $categoria) {
            $itens = array_values(array_filter($dados['itens'], fn (array $i) => $i['categoria'] === $categoria['chave']));
            if ($itens !== []) {
                $saida[] = $categoria + ['itens' => $itens];
            }
        }

        return $saida;
    }

    /** Onde a cópia de uma captura mora no disco, ou null se o nome não é dos nossos. */
    public static function caminhoDaCaptura(string $arquivo): ?string
    {
        return preg_match(self::NOME_DA_CAPTURA, $arquivo) ? 'portfolio/'.$arquivo : null;
    }

    private function ler(): array
    {
        $vazio = ['categorias' => [], 'itens' => []];
        $url = (string) config('portfolio.url');

        if ($url === '') {
            return $vazio;
        }

        $fresca = Cache::get('portfolio.fresca');
        if (is_array($fresca)) {
            return $fresca;
        }

        try {
            $resposta = Http::timeout(4)->connectTimeout(2)->acceptJson()->get($url);
            $dados = $resposta->successful() ? $this->conferir($resposta->json(), $url) : null;
        } catch (Throwable $e) {
            $dados = null;
        }

        if ($dados === null) {
            // Não respondeu, ou respondeu algo que não é o portfólio: fica o
            // último que deu certo, e a próxima tentativa espera um pouco.
            $dados = Cache::get('portfolio.ultima');
            $dados = is_array($dados) ? $dados : $vazio;
            Cache::put('portfolio.fresca', $dados, now()->addMinutes((int) config('portfolio.minutos_apos_falha', 5)));

            return $dados;
        }

        Cache::forever('portfolio.ultima', $dados);
        Cache::put('portfolio.fresca', $dados, now()->addMinutes(max(1, (int) config('portfolio.minutos', 30))));
        $this->limparCapturasSemDono($dados);

        return $dados;
    }

    /** Devolve só o que o site sabe mostrar; null se a resposta não tem a forma esperada. */
    private function conferir(mixed $bruto, string $url): ?array
    {
        if (! is_array($bruto) || ! is_array($bruto['categorias'] ?? null) || ! is_array($bruto['itens'] ?? null)) {
            return null;
        }

        $categorias = [];
        foreach ($bruto['categorias'] as $c) {
            $chave = is_array($c) ? (string) ($c['chave'] ?? '') : '';
            if (preg_match('/^[a-z0-9-]{1,40}$/', $chave) && is_string($c['nome'] ?? null)) {
                $categorias[$chave] = [
                    'chave' => $chave,
                    'nome' => Str::limit(trim($c['nome']), 60, ''),
                    'descricao' => Str::limit(trim((string) ($c['descricao'] ?? '')), 200, ''),
                ];
            }
        }

        $itens = [];
        foreach (array_slice($bruto['itens'], 0, 60) as $i) {
            if (! is_array($i) || ! isset($categorias[$i['categoria'] ?? '']) || ! is_string($i['titulo'] ?? null) || trim($i['titulo']) === '') {
                continue;
            }

            $endereco = is_string($i['url'] ?? null) && str_starts_with($i['url'], 'https://') && filter_var($i['url'], FILTER_VALIDATE_URL)
                ? $i['url']
                : null;

            $itens[] = [
                'categoria' => $i['categoria'],
                'titulo' => Str::limit(trim($i['titulo']), 120, ''),
                'resumo' => is_string($i['resumo'] ?? null) ? Str::limit(trim($i['resumo']), 300, '') : null,
                'estado' => ($i['estado'] ?? null) === 'em-construcao' ? 'em-construcao' : 'no-ar',
                'nome' => is_string($i['nome'] ?? null) && trim($i['nome']) !== '' ? Str::limit(trim($i['nome']), 80, '') : null,
                'url' => $endereco,
                'captura' => is_string($i['captura'] ?? null) ? $this->copiarCaptura($i['captura'], $url) : null,
            ];
        }

        return ['categorias' => array_values($categorias), 'itens' => $itens];
    }

    /** Baixa a captura para o disco daqui e devolve o nome do arquivo; null se não deu ou não é imagem. */
    private function copiarCaptura(string $endereco, string $url): ?string
    {
        $arquivo = basename((string) parse_url($endereco, PHP_URL_PATH));
        $caminho = self::caminhoDaCaptura($arquivo);

        // Só do mesmo servidor do portfólio, e só com o nome que ele gera.
        if ($caminho === null
            || parse_url($endereco, PHP_URL_HOST) !== parse_url($url, PHP_URL_HOST)
            || parse_url($endereco, PHP_URL_SCHEME) !== parse_url($url, PHP_URL_SCHEME)) {
            return null;
        }

        $disco = Storage::disk('local');
        if ($disco->exists($caminho)) {
            // O nome muda a cada envio no Sócrates: arquivo que já está aqui é o mesmo.
            return $arquivo;
        }

        try {
            $resposta = Http::timeout(6)->connectTimeout(2)->withOptions(['allow_redirects' => false])->get($endereco);
            $corpo = $resposta->successful() ? $resposta->body() : '';
        } catch (Throwable $e) {
            return null;
        }

        $tipo = $corpo !== '' && strlen($corpo) <= self::TETO_DA_CAPTURA ? @getimagesizefromstring($corpo) : false;
        if ($tipo === false || ! in_array($tipo[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP], true)) {
            return null;
        }

        $disco->put($caminho, $corpo);

        return $arquivo;
    }

    /** Captura que saiu do portfólio não fica servida aqui para sempre. */
    private function limparCapturasSemDono(array $dados): void
    {
        $emUso = array_filter(array_column($dados['itens'], 'captura'));
        $disco = Storage::disk('local');

        foreach ($disco->files('portfolio') as $caminho) {
            if (! in_array(basename($caminho), $emUso, true)) {
                $disco->delete($caminho);
            }
        }
    }
}
