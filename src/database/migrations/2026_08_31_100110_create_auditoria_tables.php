<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trilha de alterações e retenção.
 *
 * A trilha existe nos SEIS irmãos (PADROES.md §Auditoria) e entra aqui na
 * fundação, não depois: o Sócrates foi o último a ganhar a dele e levou meses,
 * e o custo não foi implementar — foi ninguém perceber que faltava.
 *
 * Uma linha POR CAMPO alterado (`campo`, `de`, `para`), como no BHD. Responde
 * "quem mudou a data desse show?" sem ler JSON na mão. Num produto operado por
 * três pessoas e um parceiro, essa é a pergunta que aparece.
 *
 * `politicas_retencao` nasce COM linhas e com o comando de expurgo ligado — o
 * BHD deixou a tabela pronta e sem código, e o SSB nunca expurgou a trilha uma
 * vez. Dado sem prazo é dado guardado para sempre por omissão.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->morphs('auditavel');

            $table->string('evento')->comment('criado | alterado | removido | restaurado');
            $table->string('campo')->nullable();
            $table->text('de')->nullable();
            $table->text('para')->nullable();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_nome')->nullable()
                ->comment('Preservado: a trilha sobrevive à anonimização do autor');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestampTz('ocorreu_em');
            $table->timestamp('created_at')->nullable();

            $table->index('ocorreu_em');
            $table->index(['user_id', 'ocorreu_em']);
        });

        Schema::create('politicas_retencao', function (Blueprint $table) {
            $table->id();
            $table->string('recurso')->unique()
                ->comment('auditoria (e, a partir do bloco 2 da 246, lead)');
            $table->unsignedSmallInteger('meses');
            $table->text('justificativa')->nullable();
            $table->boolean('nunca_expurgar')->default(false)
                ->comment('Consentimento e prova de atendimento ao titular: a defesa da casa');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('politicas_retencao');
        Schema::dropIfExists('auditorias');
    }
};
