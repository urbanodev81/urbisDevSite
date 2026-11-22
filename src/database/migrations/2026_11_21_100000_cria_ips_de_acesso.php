<?php

declare(strict_types=1);

use Database\Seeders\PoliticasDeRetencao;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * De onde cada administrador já entrou — pendência 284 F3, sinal "acesso de
 * IP nunca visto" (decisão D do ADR 0006 do Sócrates). Molde: WJ.
 *
 * Guarda o HASH do IP com a chave do aplicativo, não o IP: a pergunta é só
 * "já vi este?", e para responder não é preciso saber qual era. O IP em claro
 * vai no aviso daquele momento e mais nada.
 *
 * Retenção de 12 meses (`PoliticasDeRetencao`, `lgpd:expurgo`): IP não visto
 * há um ano volta a ser "novo", o que é o comportamento certo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ips_de_acesso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->char('ip_hash', 64);
            $table->timestamp('primeiro_em');
            $table->timestamp('ultimo_em')->index();

            $table->unique(['user_id', 'ip_hash']);
        });

        if (! DB::pretending()) {
            (new PoliticasDeRetencao)->run();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ips_de_acesso');

        if (! DB::pretending()) {
            DB::table('politicas_retencao')->where('recurso', 'ips_de_acesso')->delete();
        }
    }
};
