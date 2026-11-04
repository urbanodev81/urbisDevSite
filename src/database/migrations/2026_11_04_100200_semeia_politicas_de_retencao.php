<?php

declare(strict_types=1);

use Database\Seeders\PoliticasDeRetencao;
use Illuminate\Database\Migrations\Migration;

/**
 * A tabela `politicas_retencao` nasceu vazia no bloco 1. Com os contatos no
 * banco, ela ganha linhas e o expurgo passa a rodar — tabela sem comando é
 * promessa (lição do BHD). Pela migration, e não só pelo seeder, para o
 * ambiente que já existe receber as linhas no `migrate`.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new PoliticasDeRetencao)->run();
    }

    public function down(): void
    {
        // Nada: as linhas são a política publicada; somem com a tabela.
    }
};
