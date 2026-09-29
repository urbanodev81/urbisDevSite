<?php

namespace App\Support\Alertas;

use App\Support\Alertas\EmailFormatado;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
            EmailFormatado::para($destino)
                ->assunto("[{$servico}] Erro 500: ".mb_strimwidth($e->getMessage(), 0, 80, '…'))
                ->titulo('Erro não tratado')
                ->paragrafo("Um erro não tratado aconteceu em {$servico}.")
                ->campos([
                    'URL' => $url,
                    'Erro' => $e::class.': '.$e->getMessage(),
                    'Onde' => $e->getFile().':'.$e->getLine(),
                ])
                ->paragrafo('Repetições do mesmo erro nos próximos '.self::FREIO_MINUTOS.' min não geram outro e-mail.')
                ->remetente($servico)
                ->assinatura("Aviso automático — {$servico}")
                ->enviar();
        } catch (Throwable $falha) {
            Log::error('[alerta-erro] falha ao avisar: '.$falha->getMessage());
        }
    }
}
