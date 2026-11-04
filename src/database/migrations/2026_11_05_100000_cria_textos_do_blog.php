<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Blog no banco (bloco 3a da pendência 246, 04/11/2026).
 *
 * Os campos são os do cabeçalho dos `.md` (`resources/blog/_MODELO.md`), e
 * texto escrito no painel vira o mesmo `UrbanoDev\Blog\Post` que o arquivo
 * vira. O corpo é guardado JÁ SANEADO (`App\Blog\SaneadorDeTexto`): o editor
 * do painel não é barreira, o servidor é. Os `.md` seguem valendo como
 * reserva, e quando o slug existe nos dois, vale o banco.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('textos', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            // Único contando os arquivados: endereço que já existiu não volta
            // apontando para outro texto.
            $table->string('slug', 120)->unique();

            $table->string('titulo');
            $table->string('descricao', 300);
            $table->longText('corpo_html');

            $table->date('data')->comment('publica neste dia; futuro = agendado');
            $table->date('atualizado')->nullable();
            $table->boolean('rascunho')->default(true);

            $table->string('autor', 120)->nullable();
            $table->string('secao', 60)->nullable();
            $table->json('tags')->nullable();

            $table->string('imagem')->nullable();
            $table->string('imagem_alt', 300)->nullable();
            $table->string('imagem_credito', 200)->nullable();

            $table->string('acao_texto', 80)->nullable();
            $table->string('acao_url')->nullable();
            $table->string('parceiro', 120)->nullable();
            $table->string('parceiro_logo')->nullable();
            $table->string('parceiro_url')->nullable();

            $table->string('origem', 10)->default('painel')->comment('painel | arquivo (veio do blog:importar)');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['rascunho', 'data']);
        });

        Schema::create('blog_imagens', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('caminho')->unique()->comment('no disco public: blog/AAAA/MM/<uuid>.webp');
            $table->string('tipo', 10)->default('texto')->comment('texto | capa');
            $table->unsignedInteger('largura');
            $table->unsignedInteger('altura');
            $table->unsignedInteger('bytes');
            $table->string('nome_original')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_imagens');
        Schema::dropIfExists('textos');
    }
};
