<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Um aparelho em que a pessoa pediu para não digitar o código de novo.
 *
 * A linha pertence à pessoa (como `push_subscriptions`), e o recorte é o
 * `user_id`, sempre escrito por quem consulta: ver `AparelhosConfiaveis`.
 */
class AparelhoConfiavel extends Model
{
    protected $table = 'aparelhos_confiaveis';

    protected $guarded = ['id'];

    // O hash não tem por que circular em prop de tela nenhuma.
    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'ultimo_uso_em' => 'datetime',
            'expira_em' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $a) => $a->uuid ??= (string) Str::uuid7());
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Vencido não abre porta nem aparece na lista. */
    public function scopeVigente(Builder $query): Builder
    {
        return $query->where('expira_em', '>', now());
    }
}
