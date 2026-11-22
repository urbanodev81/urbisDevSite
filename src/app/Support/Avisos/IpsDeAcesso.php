<?php

declare(strict_types=1);

namespace App\Support\Avisos;

use App\Models\User;
use App\Support\Security\AlertaDeSeguranca;
use Illuminate\Support\Facades\DB;

/**
 * "Administrador entrou de um IP nunca visto" — o sinal que ficou no lugar do
 * país pelo IP (decisão D do ADR 0006, 284). Sem base GeoLite2 e sem mandar IP
 * para serviço de fora. Molde: WJ.
 *
 * Só ADMINISTRADOR: é a conta que, tomada, troca contas, textos e o site
 * inteiro. A redação entra de onde estiver, e avisar cada 4G novo seria barulho.
 *
 * O PRIMEIRO acesso registrado de cada conta não avisa: é a linha de base. Sem
 * isso, o deploy desta mudança dispararia um aviso por administrador no
 * primeiro login seguinte, e o operador aprenderia a ignorar o sinal no dia um.
 */
final class IpsDeAcesso
{
    public static function registrar(User $usuario, ?string $ip): void
    {
        if ($ip === null || ! $usuario->administrador()) {
            return;
        }

        $hash = hash_hmac('sha256', $ip, (string) config('app.key'));
        $agora = now();

        $conhecido = DB::table('ips_de_acesso')->where('user_id', $usuario->id)->where('ip_hash', $hash);

        if ($conhecido->update(['ultimo_em' => $agora]) > 0) {
            return;
        }

        $temBase = DB::table('ips_de_acesso')->where('user_id', $usuario->id)->exists();

        DB::table('ips_de_acesso')->insertOrIgnore([
            'user_id' => $usuario->id,
            'ip_hash' => $hash,
            'primeiro_em' => $agora,
            'ultimo_em' => $agora,
        ]);

        if (! $temBase) {
            return;
        }

        AlertaDeSeguranca::avisar(
            EventoDoSistema::AcessoDeIpNovo->value,
            'Administrador entrou de um IP nunca visto',
            "A conta de {$usuario->name} entrou no painel de um endereço que nunca tinha usado. Se não foi a pessoa, troque a senha e desligue os aparelhos confiáveis.",
            [
                'target_email' => $usuario->email,
                'actor_ip' => $ip,
                'user_agent' => request()->userAgent(),
            ],
        );
    }
}
