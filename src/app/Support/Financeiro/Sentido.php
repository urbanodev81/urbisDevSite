<?php

declare(strict_types=1);

namespace App\Support\Financeiro;

/** Quem paga: nós (MCO2, registro de domínio) ou o cliente a nós. */
enum Sentido: string
{
    case Pagar = 'pagar';
    case Receber = 'receber';

    public function rotulo(): string
    {
        return $this === self::Pagar ? 'A pagar' : 'A receber';
    }

    /** @return list<array{valor: string, rotulo: string}> */
    public static function opcoes(): array
    {
        return array_map(fn (self $c) => ['valor' => $c->value, 'rotulo' => $c->rotulo()], self::cases());
    }
}
