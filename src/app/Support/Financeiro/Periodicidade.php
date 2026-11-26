<?php

declare(strict_types=1);

namespace App\Support\Financeiro;

use Carbon\CarbonImmutable;

/**
 * De quanto em quanto tempo vence. `seguinte()` é o que o pagamento usa para
 * empurrar o vencimento.
 *
 * Mês curto não "anda" o dia: o vencimento de 31/01 vai a 28/02 e, de lá, a
 * 28/03 (`NoOverflow` sobre a data anterior). Quem vence no fim do mês ajusta
 * a data na tela uma vez; o histórico guarda a data de cada pagamento.
 */
enum Periodicidade: string
{
    case Unica = 'unica';
    case Mensal = 'mensal';
    case Trimestral = 'trimestral';
    case Semestral = 'semestral';
    case Anual = 'anual';

    public function rotulo(): string
    {
        return match ($this) {
            self::Unica => 'Uma vez só',
            self::Mensal => 'Mensal',
            self::Trimestral => 'Trimestral',
            self::Semestral => 'Semestral',
            self::Anual => 'Anual',
        };
    }

    /** Null na `unica`: pagou, acabou. */
    public function seguinte(CarbonImmutable $vencimento): ?CarbonImmutable
    {
        return match ($this) {
            self::Unica => null,
            self::Mensal => $vencimento->addMonthsNoOverflow(1),
            self::Trimestral => $vencimento->addMonthsNoOverflow(3),
            self::Semestral => $vencimento->addMonthsNoOverflow(6),
            self::Anual => $vencimento->addYearsNoOverflow(1),
        };
    }

    /** Quanto isso pesa num mês, para o total "por mês" da tela. */
    public function meses(): ?int
    {
        return match ($this) {
            self::Unica => null,
            self::Mensal => 1,
            self::Trimestral => 3,
            self::Semestral => 6,
            self::Anual => 12,
        };
    }

    /** @return list<array{valor: string, rotulo: string}> */
    public static function opcoes(): array
    {
        return array_map(fn (self $c) => ['valor' => $c->value, 'rotulo' => $c->rotulo()], self::cases());
    }
}
