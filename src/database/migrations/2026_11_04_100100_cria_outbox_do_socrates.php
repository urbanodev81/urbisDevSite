<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Outbox do que o site conta ao Sócrates (04/11/2026, bloco 2 da 246).
 *
 * Veio do molde (Duo/Girls, 05/10/2026): o fato é GRAVADO primeiro e entregue
 * depois, quantas vezes precisar. O `id` vai como `Idempotency-Key`, e o
 * Sócrates reconhece a reentrega. Entregue, a linha é APAGADA: a outbox só
 * guarda o que ainda não chegou.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socrates_outbox', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // O fato, no particípio: 'lead.recebido'. O endereço sai do tipo, no código.
            $table->string('tipo', 60);
            $table->json('payload');
            $table->timestamp('ocorreu_em');

            $table->unsignedSmallInteger('tentativas')->default(0);
            $table->timestamp('proxima_tentativa_em');
            $table->text('ultimo_erro')->nullable();

            // Desistência: payload recusado (422) ou fato velho demais.
            $table->timestamp('descartado_em')->nullable();
            $table->timestamps();

            $table->index(['descartado_em', 'proxima_tentativa_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socrates_outbox');
    }
};
