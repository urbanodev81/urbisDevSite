<?php

declare(strict_types=1);

namespace App\Services\Socrates;

use App\Models\SocratesOutbox;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Guarda o que o site tem a contar ao Sócrates e entrega até ele confirmar.
 *
 * `registrar()` só grava: não fala com a rede, então cabe dentro de uma
 * transação e não falha porque o Sócrates caiu. `entregarPendentes()` é quem
 * manda -- chamado logo depois do registro (caminho normal, chega na hora) e
 * de minuto em minuto pelo agendador (caminho de recuperação).
 *
 * Entrega é "pelo menos uma vez": a mesma linha pode chegar duas vezes ao
 * Sócrates, e por isso vai com `Idempotency-Key`. Só entra aqui tipo cujo
 * endpoint do outro lado reconhece a chave.
 */
class OutboxDoSocrates
{
    public const LEAD_RECEBIDO = 'lead.recebido';

    /** Tipo → endpoint. Tipo novo só entra depois que o Sócrates aceitar a chave nele. */
    private const ENDPOINTS = [
        self::LEAD_RECEBIDO => '/api/site/leads',
    ];

    /** Minutos até a próxima tentativa, pela ordem; a última vale para todas as seguintes. */
    private const ESPERAS = [1, 5, 15, 60];

    /** Fato mais velho que isso não interessa mais: o resumo do Sócrates é diário. */
    private const VALIDADE_EM_HORAS = 72;

    private const DIAS_GUARDANDO_DESCARTADO = 30;

    public function __construct(private readonly ClienteDoSocrates $client) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function registrar(string $tipo, array $payload, ?Carbon $ocorreuEm = null): ?SocratesOutbox
    {
        if (! isset(self::ENDPOINTS[$tipo])) {
            throw new \InvalidArgumentException("Tipo de outbox desconhecido: {$tipo}");
        }

        // Integração desligada é "recurso desligado", como no client: sem
        // isto, ambiente sem Sócrates acumularia linha que ninguém entrega.
        if (! $this->client->configurado() || ! $this->tipoLigado($tipo)) {
            return null;
        }

        return SocratesOutbox::create([
            'tipo' => $tipo,
            'payload' => $payload,
            'ocorreu_em' => $ocorreuEm ?? now(),
            'proxima_tentativa_em' => now(),
        ]);
    }

    /**
     * @return array{entregues: int, adiadas: int, descartadas: int}
     */
    public function entregarPendentes(int $limite = 50): array
    {
        $resultado = ['entregues' => 0, 'adiadas' => 0, 'descartadas' => 0];

        if (! $this->client->configurado()) {
            return $resultado;
        }

        $pendentes = SocratesOutbox::query()
            ->whereNull('descartado_em')
            ->where('proxima_tentativa_em', '<=', now())
            ->orderBy('proxima_tentativa_em')
            ->limit($limite)
            ->get();

        foreach ($pendentes as $linha) {
            if (! $this->reservar($linha)) {
                continue;
            }

            $desfecho = $this->entregar($linha);
            $resultado[$desfecho]++;

            // Adiou porque o Sócrates não atendeu (rede, 401, 429, 5xx), e o
            // que o impede de atender esta linha impede as outras. Parar aqui
            // custa uma espera de rede por rodada, e não uma por linha -- com
            // muito na outbox, uma hora de Sócrates fora do ar são dezenas.
            if ($desfecho === 'adiadas') {
                break;
            }
        }

        return $resultado;
    }

    /**
     * `lead.recebido` depende do Sócrates aceitar o endpoint (`SOCRATES_LEADS`).
     * Tipo desligado não grava linha: ninguém a entregaria.
     */
    private function tipoLigado(string $tipo): bool
    {
        return $tipo !== self::LEAD_RECEBIDO || (bool) config('socrates.leads');
    }

    /** Apaga o que foi descartado há mais de 30 dias. Devolve quantas linhas saíram. */
    public function limpar(): int
    {
        return SocratesOutbox::query()
            ->where('descartado_em', '<', now()->subDays(self::DIAS_GUARDANDO_DESCARTADO))
            ->delete();
    }

    /**
     * Marca a tentativa ANTES de chamar a rede, num update condicional: se o
     * agendador e o job de entrega pegarem a mesma linha, só um passa. E se
     * o processo morrer no meio da chamada, a linha já está agendada de novo.
     */
    private function reservar(SocratesOutbox $linha): bool
    {
        $espera = self::ESPERAS[min($linha->tentativas, count(self::ESPERAS) - 1)];

        return SocratesOutbox::query()
            ->whereKey($linha->id)
            ->whereNull('descartado_em')
            ->where('tentativas', $linha->tentativas)
            ->update([
                'tentativas' => $linha->tentativas + 1,
                'proxima_tentativa_em' => now()->addMinutes($espera),
            ]) === 1;
    }

    /** @return 'entregues'|'adiadas'|'descartadas' */
    private function entregar(SocratesOutbox $linha): string
    {
        if ($linha->ocorreu_em->lt(now()->subHours(self::VALIDADE_EM_HORAS))) {
            return $this->descartar($linha, 'Venceu sem entrega: '.($linha->ultimo_erro ?? 'sem erro registrado'));
        }

        try {
            $resposta = $this->client->entregarComChave(self::ENDPOINTS[$linha->tipo], $linha->payload, $linha->id);
        } catch (\Throwable $e) {
            return $this->adiar($linha, $e->getMessage());
        }

        if ($resposta->successful()) {
            $linha->delete();

            return 'entregues';
        }

        // 422 é payload errado: tentar de novo não conserta bug daqui. Todo o
        // resto (401 de token trocado, 429, 5xx) se resolve do outro lado.
        if ($resposta->unprocessableEntity()) {
            return $this->descartar($linha, 'Sócrates recusou (422): '.mb_substr($resposta->body(), 0, 500));
        }

        return $this->adiar($linha, "HTTP {$resposta->status()}");
    }

    private function adiar(SocratesOutbox $linha, string $erro): string
    {
        SocratesOutbox::query()->whereKey($linha->id)->update(['ultimo_erro' => mb_substr($erro, 0, 1000)]);

        return 'adiadas';
    }

    private function descartar(SocratesOutbox $linha, string $motivo): string
    {
        SocratesOutbox::query()->whereKey($linha->id)->update([
            'descartado_em' => now(),
            'ultimo_erro' => mb_substr($motivo, 0, 1000),
        ]);

        // O payload fica na tabela; no log vai só o que identifica a linha.
        Log::warning('Outbox do Sócrates: fato descartado sem entrega.', [
            'id' => $linha->id,
            'tipo' => $linha->tipo,
            'motivo' => $motivo,
        ]);

        return 'descartadas';
    }
}
