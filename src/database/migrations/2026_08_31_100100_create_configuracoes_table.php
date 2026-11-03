<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configuração do site — o que era o `dados.js` do protótipo.
 *
 * Chave/valor e não uma tabela de uma linha só com trinta colunas: cada bloco
 * novo (um Spotify, um TikTok) seria uma migration, e "adicionar uma rede
 * social" viraria rodada de desenvolvimento. É exatamente o defeito que o BHD
 * já pagou com perfis fixos em código (PADROES.md §BHD).
 *
 * O catálogo do que existe vive em UM arquivo — `app/Support/Configuracao.php`
 * —, e a validação sai dele. É a "regra de ouro" do §Site público do tenant:
 * chave que não está no catálogo não é gravada, e a tela nunca lê a coluna
 * crua.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracoes', function (Blueprint $table) {
            $table->id();
            $table->string('chave')->unique();
            $table->text('valor')->nullable();
            $table->string('grupo', 30)->default('geral')
                ->comment('contato | redes | contratacao | seo | geral');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracoes');
    }
};
