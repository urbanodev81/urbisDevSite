<?php

declare(strict_types=1);

use App\Models\Configuracao;
use App\Models\Lancamento;
use App\Models\Texto;
use App\Support\ConfiguracaoDoSite;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Imagens de antes do pacote urbanodev/midia (etapa 3 da pendência 255, 07/11/2026).
 *
 * O urbisdev só tem produção, então NADA é movido nem reescrito: cada imagem
 * que já está no ar vira uma linha em `midias` apontando para o MESMO arquivo
 * (`blog/AAAA/MM/<uuid>.webp` e `site/<uuid>.jpg`), com o mesmo uuid. Os
 * endereços dentro dos textos continuam valendo (`midia.pastas_antigas`).
 *
 * Em seguida registra onde cada uma é usada — capa e corpo de todo texto,
 * inclusive arquivado; imagem de cada lançamento; imagem de compartilhamento
 * — para a faxina do pacote nunca achar "sem uso" o que está no ar.
 *
 * Arquivo que sumiu do disco é pulado e anotado no log. Pode rodar de novo:
 * uuid já registrado é mantido. `blog_imagens` fica, só de leitura (sai numa
 * versão seguinte). O `down()` apaga só as linhas que apontam para `blog/` e
 * `site/` (as do pacote ficam em `midias/`); os arquivos não são tocados.
 */
return new class extends Migration
{
    public function up(): void
    {
        $disco = Storage::disk('public');
        $agora = now();
        $criadas = 0;
        $puladas = [];

        $registrar = function (string $uuid, string $caminho, string $perfil, string $colecao, array $mais = []) use ($disco, $agora, &$criadas, &$puladas): void {
            if (DB::table('midias')->where('uuid', $uuid)->exists()) {
                return;
            }
            if (! $disco->exists($caminho)) {
                $puladas[] = $caminho;

                return;
            }
            $bytes = (string) $disco->get($caminho);
            $medidas = @getimagesizefromstring($bytes);
            if ($medidas === false) {
                $puladas[] = $caminho;

                return;
            }

            DB::table('midias')->insert([
                'uuid' => $uuid,
                'perfil' => $perfil,
                'colecao' => $colecao,
                'disco' => 'public',
                'caminho' => $caminho,
                'mime' => $medidas['mime'],
                'largura' => $medidas[0],
                'altura' => $medidas[1],
                'bytes' => strlen($bytes),
                'sha256' => hash('sha256', $bytes),
                'privado' => false,
                'created_at' => $agora,
                'updated_at' => $agora,
                ...$mais,
            ]);
            $criadas++;
        };

        if (Schema::hasTable('blog_imagens')) {
            foreach (DB::table('blog_imagens')->orderBy('id')->get() as $linha) {
                $registrar(strtolower($linha->uuid), $linha->caminho, $linha->tipo === 'capa' ? 'capa' : 'foto', 'blog', [
                    'nome_original' => $linha->nome_original,
                    'user_id' => $linha->user_id !== null ? (string) $linha->user_id : null,
                    'created_at' => $linha->created_at ?? $agora,
                    'updated_at' => $linha->updated_at ?? $agora,
                ]);
            }
        }

        // OG (4a) e lançamentos (4c): o nome do arquivo é o uuid; o perfil sai da medida.
        foreach ($disco->files(ConfiguracaoDoSite::PASTA_IMAGENS) as $caminho) {
            if (preg_match('~/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})\.jpg$~i', $caminho, $m) !== 1) {
                continue;
            }
            $medidas = @getimagesize($disco->path($caminho));
            $perfil = match (true) {
                $medidas !== false && [$medidas[0], $medidas[1]] === [1200, 750] => 'lancamento',
                default => 'capa',
            };
            $registrar(strtolower($m[1]), $caminho, $perfil, 'site', ['created_at' => date('Y-m-d H:i:s', (int) filemtime($disco->path($caminho)))]);
        }

        Texto::withTrashed()->get()->each(fn (Texto $t) => $t->registrarMidias());
        Lancamento::query()->get()->each(fn (Lancamento $l) => $l->sincronizarMidias('imagem', [$l->imagem]));
        $chaves = array_keys(array_filter(ConfiguracaoDoSite::CATALOGO, fn (array $c) => $c['tipo'] === 'imagem'));
        Configuracao::query()->whereIn('chave', $chaves)->get()->each(fn (Configuracao $c) => $c->sincronizarMidias('valor', [$c->valor]));

        Log::info('midia: imagens antigas registradas sem mover', [
            'criadas' => $criadas,
            'usos' => DB::table('midia_usos')->count(),
            'puladas' => $puladas,
        ]);
    }

    public function down(): void
    {
        DB::table('midias')
            ->where(fn ($q) => $q->where('caminho', 'like', 'blog/%')->orWhere('caminho', 'like', ConfiguracaoDoSite::PASTA_IMAGENS.'/%'))
            ->delete();
    }
};
