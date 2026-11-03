<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aparelhos em que a pessoa pediu para não digitar o código de novo (decisão
 * do Ricardo, 10/10/2026: aparelho confiável, com apelido).
 *
 * A linha é da PESSOA, como `push_subscriptions`: toda consulta a ela leva o
 * `user_id` na mão.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aparelhos_confiaveis', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Só o hash: quem lê a linha no banco não consegue forjar o cookie.
            $table->string('token_hash', 64)->unique();

            $table->string('apelido', 60)->comment('O nome que a pessoa deu ao aparelho');
            $table->string('descricao', 120)->nullable()->comment('Navegador e sistema, lidos do User-Agent');
            $table->string('ip', 45)->nullable();

            $table->timestamp('ultimo_uso_em');
            // Sempre preenchido: aparelho vendido ou perdido não pode seguir
            // confiável até alguém lembrar de remover.
            $table->timestamp('expira_em');
            $table->timestamps();

            $table->index(['user_id', 'expira_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aparelhos_confiaveis');
    }
};
