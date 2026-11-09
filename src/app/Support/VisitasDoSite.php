<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Throwable;
use UrbanoDev\Visitas\Visitas;

/**
 * Os números de visita na forma que o painel mostra (09/11/2026, 263).
 *
 * Período = os 30 dias que terminam hoje (no fuso do contador); a comparação é
 * com os 30 dias antes deles — número solto não diz se 340 é bom ou ruim.
 * Banco fora: devolve `null` e a tela diz que não deu para ler.
 */
class VisitasDoSite
{
    /** O nome que cada página tem no painel; o resto mostra o próprio caminho. */
    private const TITULOS = [
        'site.home' => 'Página inicial',
        'site.como-trabalhamos' => 'Como trabalhamos',
        'site.privacidade' => 'Privacidade',
        'site.trabalhos' => 'Trabalhos',
        'site.demonstracoes' => 'Demonstrações',
        'blog.index' => 'Blog',
    ];

    /** @return array<string, mixed> */
    public function paraOPainel(int $dias = 30): array
    {
        try {
            $r = Visitas::relatorio();
            $hoje = CarbonImmutable::now(config('visitas.fuso'))->startOfDay();
            $inicio = $hoje->subDays($dias - 1);

            $atual = $r->resumo($inicio, $hoje);
            $anterior = $r->resumo($inicio->subDays($dias), $inicio->subDay());

            return [
                'dias' => $dias,
                'resumo' => $atual,
                'anterior' => $anterior,
                'porDia' => array_map(fn (array $d) => $d + [
                    'rotulo' => CarbonImmutable::parse($d['dia'])->format('d/m'),
                ], $r->porDia($inicio, $hoje)),
                'paginas' => array_map(fn (array $p) => $p + [
                    'titulo' => self::TITULOS[$p['rota']] ?? $p['caminho'],
                ], $r->paginas($inicio, $hoje, 15)),
                'desde' => ($desde = $r->desde()) ? CarbonImmutable::parse($desde)->format('d/m/Y') : null,
                'erro' => false,
            ];
        } catch (Throwable $e) {
            report($e);

            return ['dias' => $dias, 'erro' => true];
        }
    }

    /** O resumo curto do Início: últimos 7 dias contra os 7 anteriores. */
    public function daSemana(): ?array
    {
        try {
            $r = Visitas::relatorio();
            $hoje = CarbonImmutable::now(config('visitas.fuso'))->startOfDay();

            return [
                'atual' => $r->resumo($hoje->subDays(6), $hoje),
                'anterior' => $r->resumo($hoje->subDays(13), $hoje->subDays(7)),
                'desde' => $r->desde(),
            ];
        } catch (Throwable) {
            return null;
        }
    }
}
