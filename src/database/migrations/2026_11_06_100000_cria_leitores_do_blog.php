<?php

declare(strict_types=1);

use Database\Seeders\PoliticasDeRetencao;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leitores do blog (bloco 3b da pendência 246, 04/11/2026): mais lidos,
 * "Este texto ajudou?" e comentários com moderação.
 *
 * A chave é o SLUG, não o id de `textos`: o texto pode vir do banco ou de um
 * `.md`, e o slug é o que as duas fontes têm em comum (trava depois de
 * publicado).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Só o agregado por dia. Nada de IP, sessão ou aparelho: não há dado
        // pessoal aqui, e por isso a leitura não tem prazo de expurgo.
        Schema::create('blog_leituras', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120);
            $table->date('dia');
            $table->unsignedInteger('total')->default(0);
            $table->unique(['slug', 'dia']);
            $table->index('dia');
        });

        // Anônimo: só o sim/não e, opcional, o que faltou.
        Schema::create('blog_votos', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->index();
            $table->boolean('util');
            $table->string('faltou', 1000)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('blog_comentarios', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('slug', 120);
            $table->string('nome', 120);
            $table->string('email')->comment('nunca aparece no site');
            $table->string('origem', 12)->default('formulario')->comment('formulario | linkedin');
            $table->string('linkedin_sub', 120)->nullable()->comment('id OIDC do LinkedIn, quando veio de lá');
            $table->text('corpo');
            $table->timestamp('consentimento_em')->nullable()->comment('aceite LGPD no envio');
            $table->string('status', 10)->default('pendente')->comment('pendente | aprovado | recusado');
            $table->foreignId('moderado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderado_em')->nullable();
            $table->text('resposta')->nullable()->comment('da equipe, aparece como urbisDev');
            $table->foreignId('respondido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('respondido_em')->nullable();
            $table->timestamp('email_enviado_em')->nullable()->comment('aviso à equipe');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['slug', 'status']);
            $table->index(['status', 'created_at']);
        });

        (new PoliticasDeRetencao)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_comentarios');
        Schema::dropIfExists('blog_votos');
        Schema::dropIfExists('blog_leituras');
    }
};
