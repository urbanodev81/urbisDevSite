<?php

namespace App\Support\Alertas;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Avisa o OPERADOR de um erro não tratado (o "500"), por e-mail.
 *
 * Existe porque, até 29/09/2026, a tela de erro dizia que a equipe tinha sido
 * avisada e nenhum aviso saía: sem Sentry configurado e sem `report`, o erro
 * morria no log do contêiner (pendência 134). Mesmo desenho do alerta de 500
 * do USI (2fa5546), que também já passou por isso (pendência 131).
 *
 * - Destino com padrão (`security_alerts.alert_email`): alerta que depende de
 *   uma variável que alguém esqueceu não é alerta.
 * - Freio de 5 min por erro DISTINTO: um defeito numa rota movimentada não
 *   pode virar centenas de e-mails — a caixa que recebe tudo é a que ninguém lê.
 * - Nunca lança: quem avisa do erro não pode ser o segundo erro.
 */
class AlertaDeErro
{
    public const FREIO_MINUTOS = 5;

    public static function avisar(Throwable $e): void
    {
        try {
            $assinatura = $e::class.'|'.$e->getFile().':'.$e->getLine().'|'.$e->getMessage();
            if (! Cache::add('alerta-erro:'.md5($assinatura), true, now()->addMinutes(self::FREIO_MINUTOS))) {
                return;
            }

            $destino = config('security_alerts.alert_email');
            if (! $destino) {
                return;
            }

            $servico = (string) config('security_alerts.service', config('app.name'));
            $url = app()->runningInConsole() ? 'cli' : request()->fullUrl();
            $corpo = implode("\n", [
                "Erro não tratado em {$servico}",
                '',
                "URL: {$url}",
                'Erro: '.$e::class.': '.$e->getMessage(),
                'Onde: '.$e->getFile().':'.$e->getLine(),
                '',
                'Repetições do mesmo erro nos próximos '.self::FREIO_MINUTOS.' min não geram outro e-mail.',
            ]);

            Mail::raw($corpo, fn ($m) => $m->to($destino)->subject("[{$servico}] Erro 500: ".mb_strimwidth($e->getMessage(), 0, 80, '…')));
        } catch (Throwable $falha) {
            Log::error('[alerta-erro] falha ao avisar: '.$falha->getMessage());
        }
    }
}
