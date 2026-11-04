<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Auditoria;
use App\Models\Contato;
use App\Models\PoliticaRetencao;
use Illuminate\Console\Command;

/**
 * Expurgo por retenção — LGPD art. 15 e 16 (04/11/2026, bloco 2 da 246).
 *
 * Veio do molde (Duo). Tabela de prazos sem comando é promessa: o BHD deixou a
 * tabela pronta e sem código, e o SSB nunca expurgou a trilha. Aqui os dois
 * entram juntos, e o comando roda todo dia pelo agendador.
 *
 * `--dry-run` só conta. Comando que apaga em lote e não sabe simular é rodado
 * com medo, e comando rodado com medo não é agendado.
 *
 * Esta é a ÚNICA exclusão real do painel, e é por LGPD: o resto arquiva.
 */
class ExpurgarPorRetencao extends Command
{
    protected $signature = 'lgpd:expurgo {--dry-run : Só conta o que seria apagado}';

    protected $description = 'Apaga o que passou do prazo de retenção escrito em politicas_retencao.';

    public function handle(): int
    {
        $simulacao = (bool) $this->option('dry-run');

        foreach (PoliticaRetencao::query()->orderBy('recurso')->get() as $politica) {
            if ($politica->nunca_expurgar) {
                $this->line("· {$politica->recurso}: protegido (nunca_expurgar).");

                continue;
            }

            $limite = now()->subMonths($politica->meses);

            $consulta = match ($politica->recurso) {
                'auditoria' => Auditoria::query()->where('ocorreu_em', '<', $limite),

                // `withTrashed`: contato arquivado continua sendo dado pessoal.
                // Expurgar só o visível deixaria o arquivo guardando para sempre.
                'contato' => Contato::withTrashed()->where('created_at', '<', $limite),

                default => null,
            };

            if ($consulta === null) {
                continue;
            }

            $quantos = (clone $consulta)->count();

            if ($simulacao) {
                $this->line("· {$politica->recurso}: {$quantos} linha(s) além de {$politica->meses} meses.");

                continue;
            }

            // `forceDelete` no que tem soft delete: expurgo que só marca
            // `deleted_at` não expurga nada.
            $politica->recurso === 'contato' ? $consulta->forceDelete() : $consulta->delete();

            $this->info("· {$politica->recurso}: {$quantos} linha(s) apagada(s).");
        }

        return self::SUCCESS;
    }
}
