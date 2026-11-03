<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Uma linha da trilha de alterações.
 *
 * Só nasce e é lida — **nunca é alterada nem apagada pela aplicação**. Trilha
 * que a aplicação sabe editar não serve de trilha. O único caminho de saída
 * daqui é o expurgo por retenção (`retencao:expurgar`), que respeita a coluna
 * `nunca_expurgar` da política.
 */
class Auditoria extends Model
{
    protected $table = 'auditorias';

    protected $guarded = ['id'];

    /** Uma linha de trilha não é atualizada nunca; a coluna existiria para mentir. */
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['ocorreu_em' => 'datetime'];
    }

    public function auditavel(): MorphTo
    {
        return $this->morphTo();
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function nomeDoAutor(): string
    {
        return $this->user_nome ?? $this->autor?->name ?? 'sistema';
    }

    /** "Show", "Integrante" — o nome curto, para a tela. */
    public function tipoLegivel(): string
    {
        return class_basename((string) $this->auditavel_type);
    }
}
