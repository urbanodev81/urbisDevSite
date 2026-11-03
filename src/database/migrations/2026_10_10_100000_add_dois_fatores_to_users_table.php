<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Segundo fator por aplicativo autenticador (pendência 136). As colunas têm
 * os nomes do BHD, de onde o `Auditable` daqui veio já com as duas primeiras
 * na lista do que nunca vai para a trilha.
 *
 * `dois_fatores_confirmado_em` nulo com segredo preenchido é ativação em
 * curso: a pessoa pediu para ligar e ainda não provou que o aplicativo lê o
 * código. O segundo fator só vale quando a data existe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('dois_fatores_segredo')->nullable();
            $table->text('dois_fatores_codigos_recuperacao')->nullable();
            $table->timestamp('dois_fatores_confirmado_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'dois_fatores_segredo',
                'dois_fatores_codigos_recuperacao',
                'dois_fatores_confirmado_em',
            ]);
        });
    }
};
