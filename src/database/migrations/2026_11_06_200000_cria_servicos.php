<?php

declare(strict_types=1);

use App\Support\ServicosDoSite;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * As frentes da seção "O que fazemos" (bloco 4b da 246, 06/11/2026).
 *
 * Até aqui eram 4 cartões no `welcome`, repetidos com outra redação no
 * `OfferCatalog` do JSON-LD e no `llms.txt`. Agora é uma lista só, e as três
 * saídas leem dela (`App\Support\ServicosDoSite`).
 *
 * Nasce semeada com os 4 de hoje (`ServicosDoSite::PADRAO`), pelo
 * `DB::table` e não pelo model: semente não é alteração de ninguém e não vai
 * para a trilha. `icone` e `cor` são chaves das listas fechadas do mesmo
 * arquivo; o banco não guarda SVG nem CSS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 80);
            $table->string('frente', 40);
            $table->text('texto');
            $table->string('icone', 30);
            $table->string('cor', 30);
            $table->string('situacao', 20)->default('no_ar');
            $table->string('link_url', 255)->nullable();
            $table->string('link_rotulo', 40)->nullable();
            $table->string('nome_no_catalogo', 120)->nullable();
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['ativo', 'ordem']);
        });

        $agora = now();
        foreach (ServicosDoSite::PADRAO as $i => $servico) {
            DB::table('servicos')->insert([...$servico, 'ordem' => $i + 1, 'ativo' => true, 'created_at' => $agora, 'updated_at' => $agora]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('servicos');
    }
};
