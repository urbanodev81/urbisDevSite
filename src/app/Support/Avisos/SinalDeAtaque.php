<?php

declare(strict_types=1);

namespace App\Support\Avisos;

use Illuminate\Support\Facades\Cache;

/**
 * Contador com janela para os sinais de ataque que um evento sozinho não diz
 * (decisão D do ADR 0006, 284). Uma senha errada é a dona esquecida; vinte
 * em dez minutos, de três IPs, não é.
 *
 * Janela FIXA (o relógio é fatiado em blocos de N minutos), não deslizante:
 * uma rajada que cruza a virada de bloco pode precisar de até o dobro para
 * acusar. Em troca são dois `Cache::` por chamada e nenhuma tabela. Para
 * "isto está acontecendo agora?" basta.
 *
 * Avisa UMA vez por janela: devolve `true` só na chamada que alcança o
 * limite. As seguintes, no mesmo bloco, ficam caladas — o freio do Sócrates
 * cuida do resto.
 */
final class SinalDeAtaque
{
    /** Falhas de login, somando contas e IPs, para acusar rajada. */
    public const RAJADA_LIMITE = 20;

    public const RAJADA_JANELA_MIN = 10;

    /** IPs distintos na mesma janela. Um IP só é a dona insistindo ou o teto por IP pegando. */
    public const RAJADA_IPS_MINIMOS = 3;

    /** Códigos errados do segundo fator, por conta. Igual ao teto do desafio. */
    public const SEGUNDO_FATOR_LIMITE = 5;

    public const SEGUNDO_FATOR_JANELA_MIN = 15;

    /** Robôs pegos na armadilha do formulário, somando todos. */
    public const ROBO_LIMITE = 10;

    public const ROBO_JANELA_MIN = 60;

    /**
     * Conta mais uma ocorrência e diz se ESTA alcançou o limite.
     *
     * @param  string|null  $ip  quando dado, também exige `$ipsMinimos` IPs distintos
     */
    public static function contar(string $sinal, int $limite, int $janelaMinutos, ?string $ip = null, int $ipsMinimos = 1): bool
    {
        $bloco = intdiv(now()->getTimestamp(), $janelaMinutos * 60);
        $chave = "sinal-de-ataque:{$sinal}:{$bloco}";
        $validade = now()->addMinutes($janelaMinutos * 2);

        Cache::add($chave, 0, $validade);
        $total = (int) Cache::increment($chave);

        $ips = 1;

        if ($ip !== null) {
            // Não é atômico: duas falhas simultâneas podem perder um IP. O
            // contador principal é atômico; o conjunto só diz "mais de um".
            $conjunto = Cache::get("{$chave}:ips", []);
            $conjunto[hash('xxh3', $ip)] = true;
            Cache::put("{$chave}:ips", $conjunto, $validade);
            $ips = count($conjunto);
        }

        if ($total < $limite || $ips < $ipsMinimos) {
            return false;
        }

        // A primeira chamada em que as duas condições valem. Com IPs, o total
        // pode já ter passado do limite quando o terceiro IP chega.
        return Cache::add("{$chave}:avisado", true, $validade);
    }
}
