<?php

declare(strict_types=1);

use App\Support\ServicosDoSite;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * O 5º cartão de "O que fazemos": a central de chamados (HDU 7f, 27/11/2026),
 * sem nome de produto.
 *
 * Só acrescenta, e só se não houver cartão com o mesmo título: a lista é da
 * tela Site › Serviços, e quem editou lá ganha da semente. Banco novo já nasce
 * com ele pela migration de 06/11 (`PADRAO` inclui o cartão), e aqui não faz
 * nada. Entra no fim da fila (`ordem` = maior + 1). Pelo `DB::table`, como a
 * semente de 06/11: não vai para a trilha.
 */
return new class extends Migration
{
    public function up(): void
    {
        $cartao = ServicosDoSite::CENTRAL_DE_CHAMADOS;

        if (DB::table('servicos')->where('titulo', $cartao['titulo'])->exists()) {
            return;
        }

        $agora = now();
        DB::table('servicos')->insert([
            ...$cartao,
            'ordem' => (int) DB::table('servicos')->max('ordem') + 1,
            'ativo' => true,
            'created_at' => $agora,
            'updated_at' => $agora,
        ]);

        ServicosDoSite::esquecer();
    }

    public function down(): void
    {
        // Não apaga: depois do up o cartão é da tela, e pode ter sido editado.
    }
};
