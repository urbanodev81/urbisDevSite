<?php

declare(strict_types=1);

namespace App\Support\Avisos;

/**
 * O catálogo de avisos do urbisdev (pendência 284 F3, ADR 0006 do Sócrates).
 *
 * Aqui só existe a camada 1: tudo vai ao OPERADOR pelo Sócrates, como origem
 * `nosso`, e é LÁ que a matriz decide se vira push, Telegram ou resumo.
 * Não há camada 2 (alçada dentro do painel): as contas do painel são da casa,
 * e quem recebe pelo Sócrates já é quem cuida. O dia em que entrar alguém de
 * fora com alçada (ex.: a redação recebendo "comentário novo"), o molde está
 * na WJ (`EventoDoSistema::permissao()`, `PreferenciasDeAviso`).
 *
 * O lead do formulário NÃO está aqui: ele vai pelo `lead.recebido`
 * (→ `/api/site/leads`, card no kanban), que já existia.
 *
 * ⚠️ A CHAVE é contrato com o Sócrates (`App\Domain\Avisos\Enums\EventoDeServico`
 * de lá). Mudar uma chave aqui sem mudar lá faz o evento chegar como "fora do
 * catálogo", sem a urgência certa.
 */
enum EventoDoSistema: string
{
    case Erro500 = 'erro.500';
    case ContaTravada = 'seguranca.conta_travada';
    case RajadaNoLogin = 'seguranca.rajada_login';
    case SegundoFatorEmSerie = 'seguranca.segundo_fator_em_serie';
    case RoboNoFormulario = 'seguranca.robo_no_formulario';
    case AcessoDeIpNovo = 'seguranca.acesso_ip_novo';

    /** Todos vão ao Sócrates. Fica escrito para o dia em que um não for. */
    public function paraOperador(): bool
    {
        return true;
    }
}
