<?php

declare(strict_types=1);

namespace App\Models;

use App\Blog\SaneadorDeTexto;
use App\Support\Auditoria\Auditable;
use App\Support\TemUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UrbanoDev\Blog\Post;
use UrbanoDev\Midia\Concerns\UsaMidias;
use UrbanoDev\Midia\Models\Midia;

/**
 * Texto do blog escrito no painel (bloco 3a da 246, 04/11/2026). Vira o mesmo
 * `Post` que um `.md` vira; quem lê é a `App\Blog\FonteDoBanco`.
 *
 * `corpo_html` chega aqui já saneado. Na trilha, a mudança do corpo entra sem
 * o valor ("o texto mudou"): HTML inteiro a cada salvar afogaria a tabela, e o
 * que se pergunta à trilha é quem mexeu e quando.
 */
class Texto extends Model
{
    use Auditable {
        mudancasAuditaveis as protected mudancasDaTrilha;
    }
    use SoftDeletes;
    use TemUuid;
    use UsaMidias;

    public const SITUACOES = ['rascunho' => 'Rascunho', 'agendado' => 'Agendado', 'publicado' => 'Publicado'];

    protected $table = 'textos';

    protected $guarded = ['id'];

    /**
     * Imagens (255, 07/11/2026): a cada gravação, registra no pacote onde cada
     * imagem é usada (a faxina nunca leva o que está num texto, nem arquivado)
     * e guarda a última descrição dada a ela, que a galeria oferece na próxima vez.
     */
    protected static function booted(): void
    {
        static::saved(fn (Texto $texto) => $texto->registrarMidias());
    }

    /** Também usado pela migration que registrou as imagens de antes do pacote. */
    public function registrarMidias(): void
    {
        $this->sincronizarMidias('capa', [$this->imagem]);
        $this->sincronizarMidias('corpo', Midia::uuidsNoTexto($this->corpo_html));
        $this->lembrarDescricoes();
    }

    public function rotuloParaMidia(string $campo): string
    {
        return $campo === 'capa' ? $this->titulo.' (capa)' : $this->titulo;
    }

    private function lembrarDescricoes(): void
    {
        $descricoes = [];
        if ($this->imagem && $this->imagem_alt && ($uuid = Midia::uuidDe($this->imagem))) {
            $descricoes[$uuid] = $this->imagem_alt;
        }
        preg_match_all('~<img\b[^>]*>~i', (string) $this->corpo_html, $tags);
        foreach ($tags[0] as $tag) {
            if (preg_match('~\ssrc="([^"]+)"~', $tag, $src) === 1 && preg_match('~\salt="([^"]+)"~', $tag, $alt) === 1
                && ($uuid = Midia::uuidsNoTexto(html_entity_decode($src[1]))[0] ?? null)) {
                $descricoes[$uuid] = mb_substr(html_entity_decode($alt[1]), 0, 300);
            }
        }
        foreach ($descricoes as $uuid => $descricao) {
            Midia::query()->where('uuid', $uuid)->where(fn ($q) => $q->whereNull('descricao')->orWhere('descricao', '!=', $descricao))
                ->update(['descricao' => $descricao]);
        }
    }

    protected function casts(): array
    {
        return [
            'data' => 'immutable_date',
            'atualizado' => 'immutable_date',
            'rascunho' => 'boolean',
            'tags' => 'array',
        ];
    }

    public function situacao(): string
    {
        return match (true) {
            $this->rascunho => 'rascunho',
            $this->data->startOfDay()->gt(CarbonImmutable::now()) => 'agendado',
            default => 'publicado',
        };
    }

    /** O endereço público já pode ter sido visto, lido e compartilhado: não muda mais. */
    public function jaFoiPublicado(): bool
    {
        return $this->situacao() === 'publicado';
    }

    public function paraPost(): Post
    {
        return new Post(
            slug: $this->slug,
            titulo: $this->titulo,
            descricao: $this->descricao,
            data: $this->data,
            atualizado: $this->atualizado,
            // Sem autor no texto, assina quem responde pela casa (auditoria SEO/GEO de
            // 06/11/2026: artigo sem autor pesa contra no buscador e na citação por IA).
            autor: $this->autor ?: config('blog.autor_padrao'),
            imagem: $this->imagem,
            imagemAlt: $this->imagem_alt,
            imagemCredito: $this->imagem_credito,
            rascunho: $this->rascunho,
            acaoTexto: $this->acao_texto,
            acaoUrl: $this->acao_url,
            corpo: '',
            tags: array_values($this->tags ?? []),
            parceiro: $this->parceiro,
            parceiroLogo: $this->parceiro_logo,
            parceiroUrl: $this->parceiro_url,
            secao: $this->secao,
            htmlPronto: app(SaneadorDeTexto::class)->paraLeitura($this->corpo_html),
            fonte: 'banco',
        );
    }

    /** @return array<string, array{0: ?string, 1: ?string}> */
    protected function mudancasAuditaveis(): array
    {
        $mudancas = $this->mudancasDaTrilha();
        if (array_key_exists('corpo_html', $mudancas)) {
            $mudancas['corpo_html'] = [null, null];
        }

        return $mudancas;
    }
}
