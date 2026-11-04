<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Auditoria\Auditable;
use App\Support\TemUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Comentário num texto do blog (bloco 3b da 246, 04/11/2026).
 *
 * Todo comentário nasce `pendente` e só aparece no site depois de aprovado no
 * painel (decisão dele). O e-mail NUNCA vai para a página: serve para a equipe
 * responder em particular, se precisar. Quem entrou pelo LinkedIn ganha o selo
 * "verificado pelo LinkedIn", mas passa pela mesma fila.
 *
 * Auditado: aprovar, recusar e responder entram na trilha (quem e quando).
 */
class BlogComentario extends Model
{
    use Auditable;
    use SoftDeletes;
    use TemUuid;

    public const PENDENTE = 'pendente';

    public const APROVADO = 'aprovado';

    public const RECUSADO = 'recusado';

    public const STATUS = [
        self::PENDENTE => ['rotulo' => 'Aguardando', 'token' => 'warning', 'icone' => 'clock'],
        self::APROVADO => ['rotulo' => 'No ar', 'token' => 'success', 'icone' => 'circle-check'],
        self::RECUSADO => ['rotulo' => 'Recusado', 'token' => 'danger', 'icone' => 'circle-slash'],
    ];

    public const ORIGEM_FORMULARIO = 'formulario';

    public const ORIGEM_LINKEDIN = 'linkedin';

    protected $table = 'blog_comentarios';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'consentimento_em' => 'immutable_datetime',
            'moderado_em' => 'immutable_datetime',
            'respondido_em' => 'immutable_datetime',
            'email_enviado_em' => 'immutable_datetime',
        ];
    }

    /** @param Builder<self> $q */
    public function scopeAprovados(Builder $q): void
    {
        $q->where('status', self::APROVADO);
    }

    /** @param Builder<self> $q */
    public function scopePendentes(Builder $q): void
    {
        $q->where('status', self::PENDENTE);
    }

    public function moderador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderado_por');
    }

    public function verificadoPeloLinkedin(): bool
    {
        return $this->origem === self::ORIGEM_LINKEDIN;
    }

    /** "Maria Souza" → "MS". No lugar da foto, que fica no servidor do LinkedIn. */
    public function iniciais(): string
    {
        $partes = preg_split('/\s+/u', trim($this->nome)) ?: [];
        $primeira = mb_substr($partes[0] ?? '?', 0, 1);
        $ultima = count($partes) > 1 ? mb_substr((string) end($partes), 0, 1) : '';

        return mb_strtoupper($primeira.$ultima);
    }
}
