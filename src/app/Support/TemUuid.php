<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * UUID v7 na criação, e a rota resolve por ele.
 *
 * Vale para o que é exposto por URL — show, integrante, vídeo, foto, pedido de
 * contratação. Não é universal por gosto: id sequencial na URL conta quantos
 * pedidos de contratação a banda recebeu e permite passear pelos vizinhos.
 * Música e pergunta seguem com id, porque nunca aparecem em URL pública.
 *
 * v7 e não v4: é ordenável no tempo, então o índice único não fragmenta como
 * um v4 aleatório fragmenta.
 */
trait TemUuid
{
    public static function bootTemUuid(): void
    {
        static::creating(function (Model $m): void {
            if (empty($m->uuid)) {
                $m->uuid = (string) Str::uuid7();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Valor que não é UUID vira 404. Sem isto, `/painel/shows/abc` levava
     * "abc" direto à coluna `uuid` do Postgres (22P02) e todo link torto do
     * painel virava "erro interno" — pendência 133 (29/09/2026).
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        abort_if(($field ?? $this->getRouteKeyName()) === 'uuid' && ! Str::isUuid((string) $value), 404);

        return parent::resolveRouteBindingQuery($query, $value, $field);
    }
}
