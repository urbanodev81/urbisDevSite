<?php

declare(strict_types=1);

namespace App\Support\Avisos;

use App\Models\User;
use App\Support\Security\AlertaDeSeguranca;
use Illuminate\Http\Request;

/**
 * Os sinais de ataque que nascem no login e nos formulários públicos
 * (decisão D do ADR 0006, 284 F3; molde da WJ). Cada método conta uma
 * ocorrência e, na que alcança o limite, avisa o operador. Nenhum deles
 * lança: é chamado no meio de uma recusa.
 */
final class SinaisDoLogin
{
    /** Senha errada ou tentativa barrada pelo teto: soma para a rajada. */
    public static function falhaDeSenha(Request $request): void
    {
        rescue(function () use ($request) {
            if (! SinalDeAtaque::contar('rajada-login', SinalDeAtaque::RAJADA_LIMITE, SinalDeAtaque::RAJADA_JANELA_MIN, (string) $request->ip(), SinalDeAtaque::RAJADA_IPS_MINIMOS)) {
                return;
            }

            AlertaDeSeguranca::avisar(
                EventoDoSistema::RajadaNoLogin->value,
                'Rajada de tentativas no login do painel',
                SinalDeAtaque::RAJADA_LIMITE.' tentativas recusadas em até '.SinalDeAtaque::RAJADA_JANELA_MIN
                    .' min, de pelo menos '.SinalDeAtaque::RAJADA_IPS_MINIMOS.' IPs diferentes. Cara de lista de senhas vazadas.',
                ['actor_ip' => $request->ip(), 'user_agent' => $request->userAgent()],
            );
        }, report: false);
    }

    /** Código do segundo fator errado. A senha desta conta estava CERTA. */
    public static function falhaDoSegundoFator(User $usuario, Request $request): void
    {
        rescue(function () use ($usuario, $request) {
            if (! SinalDeAtaque::contar('segundo-fator:'.$usuario->id, SinalDeAtaque::SEGUNDO_FATOR_LIMITE, SinalDeAtaque::SEGUNDO_FATOR_JANELA_MIN)) {
                return;
            }

            AlertaDeSeguranca::avisar(
                EventoDoSistema::SegundoFatorEmSerie->value,
                'Segundo fator errando em série',
                "A conta de {$usuario->name} passou pela senha e errou o código ".SinalDeAtaque::SEGUNDO_FATOR_LIMITE
                    .' vezes em até '.SinalDeAtaque::SEGUNDO_FATOR_JANELA_MIN.' min. Se não foi a pessoa, a senha vazou: troque já.',
                ['target_email' => $usuario->email, 'actor_ip' => $request->ip(), 'user_agent' => $request->userAgent()],
            );
        }, report: false);
    }

    /**
     * Robô num formulário público (contato, demonstração, comentário).
     *
     * O site não tem campo-isca como a WJ: a armadilha daqui é o Altcha. Um
     * captcha recusado é quase sempre robô (o widget de verdade só envia
     * depois de resolver), então a recusa em série é o mesmo sinal.
     */
    public static function roboNoFormulario(Request $request): void
    {
        rescue(function () use ($request) {
            if (! SinalDeAtaque::contar('robo-formulario', SinalDeAtaque::ROBO_LIMITE, SinalDeAtaque::ROBO_JANELA_MIN)) {
                return;
            }

            AlertaDeSeguranca::avisar(
                EventoDoSistema::RoboNoFormulario->value,
                'Robô nos formulários do site',
                SinalDeAtaque::ROBO_LIMITE.' envios com o captcha recusado em até '.SinalDeAtaque::ROBO_JANELA_MIN
                    .' min (contato, demonstrações ou comentários). Nada foi gravado; o captcha e o limite por IP seguem de pé.',
                ['actor_ip' => $request->ip(), 'user_agent' => $request->userAgent()],
            );
        }, report: false);
    }
}
