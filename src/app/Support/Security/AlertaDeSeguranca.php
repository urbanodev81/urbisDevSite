<?php

namespace App\Support\Security;

use App\Support\Alertas\EmailFormatado;
use App\Support\Avisos\AvisoAoOperador;
use App\Support\Avisos\EventoDoSistema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

use function Illuminate\Support\defer;

/**
 * Avisa o OPERADOR (nós) de um evento de segurança: pelo Sócrates e por e-mail.
 *
 * Desde 21/11/2026 (pendência 284 F3) o evento cuja chave está no catálogo
 * (`EventoDoSistema`, ex.: `seguranca.conta_travada`) vai também à outbox do
 * Sócrates, que o entrega por push e Telegram pela matriz do operador. O mesmo
 * freio vale para os dois caminhos.
 *
 * Mesma assinatura do AlertaDeSeguranca dos irmãos que têm a trilha
 * `security_alerts` (USI, SSB, Synapse, Sócrates, Movana), para o listener ser
 * igual em todos. Aqui não há tabela nem Telegram/WhatsApp: o aviso é um
 * e-mail, depois da resposta e com freio (29/09/2026, pendência 134).
 *
 * ⚠️ `use function Illuminate\Support\defer` é obrigatório: onde houver
 * Swoole, o `defer()` global é o da extensão e explode fora de corrotina.
 */
class AlertaDeSeguranca
{
    /**
     * @param  array<string, mixed>  $contexto  target_email, actor_ip, user_agent, changes
     */
    public static function avisar(
        string $evento,
        string $titulo,
        string $descricao,
        array $contexto = [],
        ?string $freio = null,
        int $minutos = 15,
    ): void {
        if ($freio !== null && ! Cache::add('alerta-seguranca:'.$evento.':'.$freio, true, now()->addMinutes($minutos))) {
            return;
        }

        if ($doCatalogo = EventoDoSistema::tryFrom($evento)) {
            $ip = $contexto['actor_ip'] ?? null;
            $conta = AvisoAoOperador::mascarar($contexto['target_email'] ?? null);

            AvisoAoOperador::relatar($doCatalogo, $titulo, trim($descricao
                .($conta ? " Conta: {$conta}." : '')
                .($ip ? " IP: {$ip}." : '')));
        }

        defer(function () use ($evento, $titulo, $descricao, $contexto) {
            try {
                $destino = config('security_alerts.alert_email');
                if (! $destino) {
                    return;
                }

                $servico = (string) config('security_alerts.service', config('app.name'));
                EmailFormatado::para($destino)
                    ->assunto("[{$servico}] {$titulo}")
                    ->titulo($titulo)
                    ->paragrafo($descricao)
                    ->campo('Evento', $evento)
                    ->campos($contexto)
                    ->remetente($servico)
                    ->assinatura("Aviso automático — {$servico}")
                    ->enviar();
            } catch (Throwable $e) {
                Log::error('[alerta-seguranca] falha ao avisar: '.$e->getMessage());
            }
        });
    }
}
