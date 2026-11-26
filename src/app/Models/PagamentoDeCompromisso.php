<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Auditoria\Auditable;
use App\Support\Financeiro\FormaDePagamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uma linha do histórico de pagamentos (Financeiro, 26/11/2026). Errou? Estorna
 * (`estornado_em`): a linha fica, riscada. Nunca se apaga.
 */
class PagamentoDeCompromisso extends Model
{
    use Auditable;

    protected $table = 'compromisso_pagamentos';

    protected $fillable = ['referente_a', 'valor_centavos', 'pago_em', 'forma', 'observacao', 'estornado_em'];

    protected function casts(): array
    {
        return [
            'referente_a' => 'immutable_date',
            'pago_em' => 'immutable_date',
            'valor_centavos' => 'integer',
            'forma' => FormaDePagamento::class,
            'estornado_em' => 'immutable_datetime',
        ];
    }

    public function compromisso(): BelongsTo
    {
        return $this->belongsTo(Compromisso::class);
    }
}
