<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Auditoria\Auditable;
use UrbanoDev\Financeiro\Models\Compromisso as CompromissoDoPacote;

/**
 * Uma coisa que vence (Financeiro, 26/11/2026). A regra (pagar, estornar,
 * situação, nunca apagar) mora no pacote `urbanodev/financeiro`; aqui só entra
 * a trilha de auditoria. As travas do aviso são gravadas direto na consulta e
 * não vão para a trilha (mudam todo dia e não são alteração de ninguém).
 */
class Compromisso extends CompromissoDoPacote
{
    use Auditable;

    protected $table = 'compromissos';
}
