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

    public const SITUACOES = ['rascunho' => 'Rascunho', 'agendado' => 'Agendado', 'publicado' => 'Publicado'];

    protected $table = 'textos';

    protected $guarded = ['id'];

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
            autor: $this->autor,
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
