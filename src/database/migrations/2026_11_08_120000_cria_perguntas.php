<?php

declare(strict_types=1);

use App\Support\PerguntasDoSite;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * As perguntas de "Como trabalhamos" (bloco 4d da 246, 08/11/2026).
 *
 * Nasce semeada com as 7 de hoje (`PerguntasDoSite::PADRAO`), pelo `DB::table`
 * e não pelo model: semente não é alteração de ninguém e não vai para a trilha.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perguntas', function (Blueprint $table) {
            $table->id();
            $table->string('pergunta', 200);
            $table->string('explicacao', 300)->nullable();
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['ativo', 'ordem']);
        });

        $agora = now();
        foreach (PerguntasDoSite::PADRAO as $i => $pergunta) {
            DB::table('perguntas')->insert([...$pergunta, 'ordem' => $i + 1, 'ativo' => true, 'created_at' => $agora, 'updated_at' => $agora]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('perguntas');
    }
};
