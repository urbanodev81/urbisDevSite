<?php

use Database\Seeders\PerfisESeguranca;
use Illuminate\Database\Migrations\Migration;

/**
 * Perfis e permissões nascem com o `migrate` (03/11/2026): em ambiente que já
 * existe, permissão só do seeder deixava a tela em 403 até para o
 * administrador (armadilha paga nos irmãos). O seeder é idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new PerfisESeguranca)->run();
    }

    public function down(): void
    {
        // Nada: apagar perfil levaria junto quem o veste.
    }
};
