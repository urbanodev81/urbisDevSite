<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Auditoria\Auditable;
use UrbanoDev\Financeiro\Models\Pagamento;

/** Uma linha do histórico de pagamentos (pacote `urbanodev/financeiro`) com a trilha de auditoria. */
class PagamentoDeCompromisso extends Pagamento
{
    use Auditable;

    protected $table = 'compromisso_pagamentos';
}
