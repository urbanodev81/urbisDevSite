<?php

declare(strict_types=1);

namespace App\Support\Financeiro;

/** O que é a coisa que vence (26/11/2026). O rótulo é o que a tela mostra. */
enum TipoDeCompromisso: string
{
    case Hospedagem = 'hospedagem';
    case Dominio = 'dominio';
    case Mensalidade = 'mensalidade';
    case Lembrete = 'lembrete';
    case Outro = 'outro';

    public function rotulo(): string
    {
        return match ($this) {
            self::Hospedagem => 'Hospedagem',
            self::Dominio => 'Domínio',
            self::Mensalidade => 'Mensalidade de cliente',
            self::Lembrete => 'Lembrete',
            self::Outro => 'Outro',
        };
    }

    /** @return list<array{valor: string, rotulo: string}> */
    public static function opcoes(): array
    {
        return array_map(fn (self $c) => ['valor' => $c->value, 'rotulo' => $c->rotulo()], self::cases());
    }
}
