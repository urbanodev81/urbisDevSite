<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Socrates\OutboxDoSocrates;
use Illuminate\Console\Command;

/**
 * Entrega ao Sócrates o que ficou parado na outbox — 04/11/2026 (veio do molde).
 *
 * Roda de minuto em minuto pelo agendador. No dia normal não acha nada: quem
 * registra já tenta entregar na hora. Este comando é o caminho de quando o
 * Sócrates estava fora do ar.
 *
 * Nunca falha: fila de aviso atrasada não é motivo de alarme no agendador.
 */
class EntregarOutboxDoSocrates extends Command
{
    protected $signature = 'socrates:entregar-outbox';

    protected $description = 'Entrega ao Sócrates os avisos pendentes na outbox';

    public function handle(OutboxDoSocrates $outbox): int
    {
        $r = $outbox->entregarPendentes();
        $limpas = $outbox->limpar();

        $this->line("Outbox do Sócrates: {$r['entregues']} entregue(s), {$r['adiadas']} adiada(s), {$r['descartadas']} descartada(s), {$limpas} antiga(s) apagada(s).");

        return self::SUCCESS;
    }
}
