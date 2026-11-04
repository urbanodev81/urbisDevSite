<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contatos do site (04/11/2026, bloco 2 da pendência 246).
 *
 * Até aqui o lead do formulário e das demonstrações era SÓ um e-mail; se o
 * SMTP falhava, o lead vivia apenas numa linha do `laravel.log`. Agora ele é
 * gravado antes do e-mail, e o e-mail vira aviso.
 *
 * ⚠️ Dado pessoal de terceiro: retenção em `politicas_retencao` (recurso
 * `contato`) e expurgo agendado (`lgpd:expurgo`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contatos', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();

            $table->string('origem', 20)->comment('contato | demonstracao');
            // Slugs, e só os que existem no config/blog: o endereço escolhe, nunca escreve.
            $table->string('demo', 40)->nullable();
            $table->string('texto', 120)->nullable();

            $table->string('nome');
            $table->string('email');
            $table->string('telefone', 20);
            $table->text('mensagem')->nullable();

            $table->string('status', 20)->default('novo')
                ->comment('novo | respondido | proposta | descartado');
            $table->text('nota')->nullable();

            $table->timestamp('consentimento_em')->nullable();
            $table->timestamp('email_enviado_em')->nullable();
            $table->string('email_erro', 500)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contatos');
    }
};
