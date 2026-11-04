<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Um fato que o site ainda não conseguiu contar ao Sócrates.
 * Ver a migration `cria_outbox_do_socrates` e `App\Services\Socrates\OutboxDoSocrates`.
 */
class SocratesOutbox extends Model
{
    use HasUuids;

    protected $table = 'socrates_outbox';

    protected $fillable = ['tipo', 'payload', 'ocorreu_em', 'proxima_tentativa_em'];

    protected $attributes = ['tentativas' => 0];

    protected $casts = [
        'payload' => 'array',
        'ocorreu_em' => 'datetime',
        'proxima_tentativa_em' => 'datetime',
        'descartado_em' => 'datetime',
        'tentativas' => 'integer',
    ];

    /** O `id` viaja como `Idempotency-Key`. v4: aleatório, sem a hora embutida. */
    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }
}
