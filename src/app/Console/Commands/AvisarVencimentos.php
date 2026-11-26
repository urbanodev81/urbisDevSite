<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Compromisso;
use App\Support\Avisos\AvisoAoOperador;
use App\Support\Avisos\EventoDoSistema;
use App\Support\Financeiro\Dinheiro;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Avisa o Ricardo do que vai vencer e do que atrasou (Financeiro, 26/11/2026).
 * Roda todo dia às 08:00 pelo `scheduler`; à mão, `--dry-run` só lista.
 *
 * Regras (uma mensagem por compromisso, nunca repetida no mesmo dia):
 *
 * - **Vence em breve:** entrou na janela (`avisar_dias_antes`) e ainda não
 *   avisou para ESTE vencimento → `financeiro.vencimento_proximo` (normal).
 *   Uma vez por vencimento; pagou, a trava zera para o próximo.
 * - **Atrasado:** passou do dia → `financeiro.vencimento_atrasado` (urgente)
 *   no primeiro dia e depois a cada 7, até registrar o pagamento ou arquivar.
 *
 * O caminho é o de todo aviso do site: outbox → Sócrates → Telegram. Sem o
 * Sócrates configurado, nada sai (e a tela Financeiro continua mostrando a
 * situação de cada um, que é o caminho sem aviso).
 */
class AvisarVencimentos extends Command
{
    public const REPETIR_ATRASO_A_CADA_DIAS = 7;

    protected $signature = 'financeiro:avisar {--dry-run : Só lista o que seria avisado, sem enviar}';

    protected $description = 'Avisa pelo Sócrates os vencimentos próximos e os atrasados do Financeiro.';

    public function handle(): int
    {
        $hoje = CarbonImmutable::today();
        $seco = (bool) $this->option('dry-run');
        $enviados = 0;

        foreach (Compromisso::query()->ativos()->orderBy('proximo_vencimento')->get() as $c) {
            $evento = $this->eventoDoDia($c, $hoje);

            if ($evento === null) {
                continue;
            }

            $this->line(sprintf('%s  %s  %s', $c->proximo_vencimento->format('d/m/Y'), $c->situacao($hoje)['texto'], $c->nome));
            $enviados++;

            if ($seco) {
                continue;
            }

            AvisoAoOperador::relatar($evento, $this->titulo($c, $evento, $hoje), $this->resumo($c), url('/painel/financeiro'));

            Compromisso::query()->whereKey($c->getKey())->update(
                $evento === EventoDoSistema::VencimentoAtrasado
                    ? ['aviso_atraso_em' => $hoje->toDateString(), 'aviso_enviado_para' => $c->proximo_vencimento->toDateString()]
                    : ['aviso_enviado_para' => $c->proximo_vencimento->toDateString()],
            );
        }

        $this->info($seco ? "{$enviados} seriam avisados." : "{$enviados} avisados.");

        return self::SUCCESS;
    }

    public function eventoDoDia(Compromisso $c, CarbonImmutable $hoje): ?EventoDoSistema
    {
        $dias = $c->diasAteVencer($hoje);

        if ($dias < 0) {
            $ultimo = $c->aviso_atraso_em;

            return $ultimo === null || (int) $ultimo->diffInDays($hoje) >= self::REPETIR_ATRASO_A_CADA_DIAS
                ? EventoDoSistema::VencimentoAtrasado
                : null;
        }

        $jaAvisou = $c->aviso_enviado_para?->isSameDay($c->proximo_vencimento) ?? false;

        return $dias <= $c->avisar_dias_antes && ! $jaAvisou ? EventoDoSistema::VencimentoProximo : null;
    }

    private function titulo(Compromisso $c, EventoDoSistema $evento, CarbonImmutable $hoje): string
    {
        $quando = $evento === EventoDoSistema::VencimentoAtrasado
            ? mb_strtolower($c->situacao($hoje)['texto'])
            : ($c->diasAteVencer($hoje) === 0 ? 'vence hoje' : 'vence em '.$c->proximo_vencimento->format('d/m'));

        return "{$c->nome} — {$quando}";
    }

    private function resumo(Compromisso $c): string
    {
        return implode(' · ', array_filter([
            $c->sentido->rotulo(),
            $c->contraparte,
            Dinheiro::formatar($c->valor_centavos),
            $c->periodicidade->rotulo(),
            'vencimento '.$c->proximo_vencimento->format('d/m/Y'),
        ]));
    }
}
