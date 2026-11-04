<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PoliticaRetencao;
use Illuminate\Database\Seeder;

/**
 * Prazo de guarda de cada coisa (04/11/2026, bloco 2 da 246).
 *
 * - `auditoria` 24 meses: dois anos respondem a qualquer "quem mexeu nisso?".
 * - `contato` 24 meses: quem pediu orçamento e não fechou costuma voltar no ano
 *   seguinte; passou disso, é dado pessoal de terceiro sem finalidade. Mesmo
 *   prazo da contratação no molde (Duo). ⚠️ Padrão a confirmar com o Ricardo.
 *
 * Idempotente (`updateOrCreate`): rodar de novo corrige deriva.
 */
class PoliticasDeRetencao extends Seeder
{
    public function run(): void
    {
        $politicas = [
            [
                'recurso' => 'auditoria',
                'meses' => 24,
                'justificativa' => 'Rastreabilidade de quem alterou contas e conteúdo do painel.',
                'nunca_expurgar' => false,
            ],
            [
                'recurso' => 'contato',
                'meses' => 24,
                'justificativa' => 'Dado pessoal de quem escreveu pelo site ou abriu uma demonstração. Passado o prazo, não há mais finalidade.',
                'nunca_expurgar' => false,
            ],
        ];

        foreach ($politicas as $politica) {
            PoliticaRetencao::updateOrCreate(['recurso' => $politica['recurso']], $politica);
        }
    }
}
