<?php

declare(strict_types=1);

use App\Support\LancamentosDoSite;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * O banner de lançamentos da home (bloco 4c da 246, 06/11/2026).
 *
 * Era `config/lancamentos.php`. Nasce semeada com os 2 slides de hoje
 * (`LancamentosDoSite::PADRAO`), pelo `DB::table` e não pelo model: semente
 * não é alteração de ninguém e não vai para a trilha. A `imagem` das duas
 * sementes aponta para `public/lancamentos/`; as que a tela sobe vão para o
 * disco `public`, em `site/`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lancamentos', function (Blueprint $table) {
            $table->id();
            $table->string('etiqueta', 60);
            $table->string('titulo', 120);
            $table->text('texto');
            $table->string('url', 255);
            $table->string('chamada', 40);
            $table->string('secundario_url', 255)->nullable();
            $table->string('secundario', 40)->nullable();
            $table->string('nota', 120)->nullable();
            $table->string('imagem', 255);
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['ativo', 'ordem']);
        });

        $agora = now();
        foreach (LancamentosDoSite::PADRAO as $i => $lancamento) {
            DB::table('lancamentos')->insert([...$lancamento, 'ordem' => $i + 1, 'ativo' => true, 'created_at' => $agora, 'updated_at' => $agora]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lancamentos');
    }
};
