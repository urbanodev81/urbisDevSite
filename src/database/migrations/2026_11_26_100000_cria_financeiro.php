<?php

declare(strict_types=1);

use Database\Seeders\PerfisESeguranca;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Financeiro do painel (26/11/2026): o que vence (hospedagem, domínio,
 * mensalidade de cliente, lembrete) e o histórico de cada pagamento.
 * Desenho em `Arquivos/analise/em-voo/urbisdev-financeiro.md`.
 *
 * **Nada aqui se apaga.** Compromisso sai de cena por `arquivado_em`;
 * pagamento lançado errado é estornado (`estornado_em`), não removido. Por
 * isso também não há política de retenção: é o histórico que ele pediu.
 *
 * Roda o `PerfisESeguranca` de novo para nascer a `financeiro.gerenciar`
 * (permissão nova em ambiente que já existe: a armadilha do seeder).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compromissos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20);
            $table->string('sentido', 10);
            $table->string('nome', 120);
            $table->string('contraparte', 120)->nullable();
            $table->unsignedBigInteger('valor_centavos')->nullable();
            $table->string('periodicidade', 12);
            $table->date('proximo_vencimento');
            $table->unsignedSmallInteger('avisar_dias_antes')->default(7);
            $table->text('observacao')->nullable();
            $table->timestamp('arquivado_em')->nullable();

            // Travas do aviso diário (`financeiro:avisar`): para QUAL
            // vencimento já saiu o "vence em breve", e quando saiu o último
            // "atrasado". Registrar pagamento zera as duas.
            $table->date('aviso_enviado_para')->nullable();
            $table->date('aviso_atraso_em')->nullable();
            $table->timestamps();

            $table->index(['arquivado_em', 'proximo_vencimento']);
        });

        Schema::create('compromisso_pagamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compromisso_id')->constrained('compromissos')->restrictOnDelete();
            $table->date('referente_a');
            $table->unsignedBigInteger('valor_centavos')->nullable();
            $table->date('pago_em');
            $table->string('forma', 20)->nullable();
            $table->string('observacao', 300)->nullable();
            $table->timestamp('estornado_em')->nullable();
            $table->timestamps();

            $table->index(['compromisso_id', 'pago_em']);
        });

        (new PerfisESeguranca)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('compromisso_pagamentos');
        Schema::dropIfExists('compromissos');
    }
};
