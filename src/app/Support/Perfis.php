<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Nome legível de um perfil.
 *
 * **NÃO é um enum, e isso é decisão.** A mesma que o BHD tomou depois de pagar
 * o preço contrário (PADROES.md §BHD): com enum fechado, "criar um perfil"
 * vira "alterar código". A fonte da verdade é a tabela `roles`; o que está
 * escrito aqui são os três perfis que o seeder cria, e qualquer perfil criado
 * depois cai no fallback e aparece na tela com um nome apresentável.
 *
 * Por que DOIS (03/11/2026, pendência 246): o painel do site é do Ricardo
 * (`administrador`) e da equipe que escreve (`redacao`: textos do blog e a
 * caixa de leads, sem contas de acesso nem configuração). Veio do molde do
 * Duo, que tem três; perfil que ninguém usa é permissão concedida sem dono.
 */
final class Perfis
{
    public const ADMINISTRADOR = 'administrador';

    public const REDACAO = 'redacao';

    /** @var array<string, string> */
    private const ROTULOS = [
        self::ADMINISTRADOR => 'Administrador',
        self::REDACAO => 'Redação',
    ];

    /** @var array<string, string> */
    private const DESCRICOES = [
        self::ADMINISTRADOR => 'Tudo, inclusive contas de acesso, configuração do site e trilha de auditoria.',
        self::REDACAO => 'Os textos do blog e a caixa de contatos. Não mexe em conta de acesso nem na configuração do site.',
    ];

    public static function rotulo(?string $nome): ?string
    {
        if ($nome === null || $nome === '') {
            return null;
        }

        return self::ROTULOS[$nome] ?? Str::headline($nome);
    }

    public static function descricao(?string $nome): ?string
    {
        return $nome === null ? null : (self::DESCRICOES[$nome] ?? null);
    }
}
