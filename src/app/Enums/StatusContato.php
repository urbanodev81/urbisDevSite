<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Em que pé está um contato do site (04/11/2026, bloco 2 da 246).
 *
 * Quatro estados e só: a caixa é uma fila de "quem ainda espera resposta", não
 * um CRM. O funil de verdade, depois da proposta, mora no Sócrates.
 */
enum StatusContato: string
{
    case Novo = 'novo';
    case Respondido = 'respondido';
    case Proposta = 'proposta';
    case Descartado = 'descartado';

    public function rotulo(): string
    {
        return match ($this) {
            self::Novo => 'Novo',
            self::Respondido => 'Respondido',
            self::Proposta => 'Virou proposta',
            self::Descartado => 'Descartado',
        };
    }

    /** Token de cor do `Chip`. A cor nunca anda sozinha: o ícone vai junto. */
    public function token(): string
    {
        return match ($this) {
            self::Novo => 'warning',
            self::Respondido => 'info',
            self::Proposta => 'success',
            self::Descartado => 'fg-subtle',
        };
    }

    public function icone(): string
    {
        return match ($this) {
            self::Novo => 'sparkles',
            self::Respondido => 'mail-check',
            self::Proposta => 'file-text',
            self::Descartado => 'circle-slash',
        };
    }

    /** @return array<int, array{valor: string, rotulo: string}> */
    public static function opcoes(): array
    {
        return array_map(fn (self $s) => ['valor' => $s->value, 'rotulo' => $s->rotulo()], self::cases());
    }
}
