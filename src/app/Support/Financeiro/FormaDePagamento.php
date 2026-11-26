<?php

declare(strict_types=1);

namespace App\Support\Financeiro;

enum FormaDePagamento: string
{
    case Pix = 'pix';
    case Boleto = 'boleto';
    case Cartao = 'cartao';
    case Transferencia = 'transferencia';
    case Dinheiro = 'dinheiro';
    case Outro = 'outro';

    public function rotulo(): string
    {
        return match ($this) {
            self::Pix => 'Pix',
            self::Boleto => 'Boleto',
            self::Cartao => 'Cartão',
            self::Transferencia => 'Transferência',
            self::Dinheiro => 'Dinheiro',
            self::Outro => 'Outro',
        };
    }

    /** @return list<array{valor: string, rotulo: string}> */
    public static function opcoes(): array
    {
        return array_map(fn (self $c) => ['valor' => $c->value, 'rotulo' => $c->rotulo()], self::cases());
    }
}
