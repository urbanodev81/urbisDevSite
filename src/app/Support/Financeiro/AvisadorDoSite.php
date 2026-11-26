<?php

declare(strict_types=1);

namespace App\Support\Financeiro;

use App\Support\Avisos\AvisoAoOperador;
use App\Support\Avisos\EventoDoSistema;
use Carbon\CarbonImmutable;
use UrbanoDev\Financeiro\Avisos\Aviso;
use UrbanoDev\Financeiro\Contratos\Avisador;
use UrbanoDev\Financeiro\Models\Compromisso;

/**
 * Por onde o aviso de vencimento do `financeiro:avisar` (pacote) sai: outbox →
 * Sócrates → Telegram, como todo aviso ao operador. O `AvisoAoOperador` só
 * grava na outbox e nunca lança; a entrega tenta por 72 h. Por isso devolve
 * true: o aviso está na fila, a trava pode ser gravada.
 */
final class AvisadorDoSite implements Avisador
{
    public function avisar(Compromisso $compromisso, Aviso $aviso, CarbonImmutable $hoje): bool
    {
        AvisoAoOperador::relatar(
            $aviso === Aviso::Atrasado ? EventoDoSistema::VencimentoAtrasado : EventoDoSistema::VencimentoProximo,
            $aviso->titulo($compromisso, $hoje),
            $compromisso->resumo(),
            url('/painel/financeiro'),
        );

        return true;
    }
}
