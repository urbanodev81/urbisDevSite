<?php

declare(strict_types=1);

namespace App\Jobs\Socrates;

use App\Services\Socrates\OutboxDoSocrates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Entrega a outbox do Sócrates fora do pedido de quem registrou — 05/10/2026.
 *
 * É o "tenta entregar na hora" de quem não pode esperar a rede: o formulário grava
 * a linha e segue, e este job leva. `tries = 1` porque insistir não é papel
 * dele -- o que não foi agora fica na outbox e o agendador reentrega.
 */
class EntregarOutboxDoSocratesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public function handle(OutboxDoSocrates $outbox): void
    {
        $outbox->entregarPendentes();
    }
}
