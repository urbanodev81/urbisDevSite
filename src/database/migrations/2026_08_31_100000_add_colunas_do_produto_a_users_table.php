<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O que o Breeze não traz e este produto precisa.
 *
 * `ativo` em vez de apagar a conta: quem sai da banda para de entrar, mas o
 * histórico de quem cadastrou qual show continua fazendo sentido. Exclusão de
 * verdade é direito do titular e passa pelo expurgo, não pelo botão da lista.
 *
 * `ultimo_acesso_em` é o que responde "essa conta ainda é usada?" na hora de
 * revisar acesso — pergunta que ninguém faz sem o dado na tela.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->string('telefone', 20)->nullable()->after('email');
            $table->boolean('ativo')->default(true)->after('telefone');
            $table->timestamp('ultimo_acesso_em')->nullable()->after('ativo');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['uuid', 'telefone', 'ativo', 'ultimo_acesso_em']);
        });
    }
};
