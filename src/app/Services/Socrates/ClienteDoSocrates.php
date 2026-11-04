<?php

declare(strict_types=1);

namespace App\Services\Socrates;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * O mínimo para entregar a outbox ao Sócrates (04/11/2026, bloco 2 da 246).
 *
 * O site não é satélite (não manda presença); por isso não veio o
 * `SocratesPresenceClient` inteiro do molde, só o envio com chave.
 */
class ClienteDoSocrates
{
    public function configurado(): bool
    {
        // Nunca fala com a rede de dentro da suíte (defeito já pago no Synapse
        // e no Movana). Teste que precise do envio finge outro ambiente.
        if (app()->environment('testing')) {
            return false;
        }

        return filled(config('socrates.url')) && filled(config('socrates.token'));
    }

    /** @param  array<string, mixed>  $dados */
    public function entregarComChave(string $caminho, array $dados, string $chave): Response
    {
        return Http::withToken((string) config('socrates.token'))
            ->withHeaders(['Idempotency-Key' => $chave])
            ->timeout(10)
            ->acceptJson()
            ->post(rtrim((string) config('socrates.url'), '/').$caminho, $dados);
    }
}
