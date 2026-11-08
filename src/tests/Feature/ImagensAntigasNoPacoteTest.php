<?php

declare(strict_types=1);

use App\Models\Lancamento;
use App\Models\Texto;
use App\Support\ConfiguracaoDoSite;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use UrbanoDev\Midia\Models\Midia;
use UrbanoDev\Midia\Models\MidiaUso;

/**
 * A migration que registra as imagens de antes do pacote (255, 07/11/2026).
 * O que se prova: nenhuma imagem no ar muda de endereço; cada uma vira linha
 * em `midias` com o mesmo uuid e o uso dela fica registrado (texto arquivado
 * incluído), então a faxina só leva a que ninguém usa; arquivo sumido é
 * pulado; rodar de novo não duplica; o `down()` não apaga arquivo nenhum.
 */
function jpegDe(int $largura, int $altura, string $formato = 'jpeg'): string
{
    $img = imagecreatetruecolor($largura, $altura);
    ob_start();
    $formato === 'webp' ? imagewebp($img) : imagejpeg($img);

    return (string) ob_get_clean();
}

beforeEach(function () {
    Storage::fake('public');
    $this->migracao = require database_path('migrations/2026_11_07_100000_registra_imagens_antigas_no_pacote.php');
});

it('registra sem mover, com os usos, e a faxina só leva a imagem sem uso', function () {
    $noTexto = '0196f3a1-0000-7000-8000-0000000000a1';
    $capaAntiga = '0196f3a1-0000-7000-8000-0000000000a2';
    $solta = '0196f3a1-0000-7000-8000-0000000000a3';
    $sumiu = '0196f3a1-0000-7000-8000-0000000000a4';
    $og = '0196f3a1-0000-7000-8000-0000000000b1';
    $banner = '0196f3a1-0000-7000-8000-0000000000b2';

    foreach ([[$noTexto, 'texto'], [$capaAntiga, 'capa'], [$solta, 'texto'], [$sumiu, 'texto']] as [$uuid, $tipo]) {
        $caminho = "blog/2026/11/{$uuid}.webp";
        if ($uuid !== $sumiu) {
            Storage::disk('public')->put($caminho, jpegDe(800, 600, 'webp'));
        }
        DB::table('blog_imagens')->insert(['uuid' => $uuid, 'caminho' => $caminho, 'tipo' => $tipo, 'largura' => 800, 'altura' => 600, 'bytes' => 1, 'nome_original' => 'foto.jpg', 'created_at' => now(), 'updated_at' => now()]);
    }
    Storage::disk('public')->put("site/{$og}.jpg", jpegDe(1200, 630));
    Storage::disk('public')->put("site/{$banner}.jpg", jpegDe(1200, 750));

    // Gravado antes de existir linha em `midias`: o salvar não acha nada para registrar.
    $texto = Texto::create([
        'slug' => 'antigo', 'titulo' => 'Texto antigo', 'descricao' => 'x', 'data' => now(), 'rascunho' => false, 'origem' => 'painel',
        'imagem' => "/storage/blog/2026/11/{$capaAntiga}.webp", 'imagem_alt' => 'A capa de antes',
        'corpo_html' => "<p>Oi.</p><img src=\"/storage/blog/2026/11/{$noTexto}.webp\" alt=\"Gráfico\">",
    ]);
    $texto->delete(); // arquivado continua usando
    Lancamento::query()->firstOrFail()->update(['imagem' => "site/{$banner}.jpg"]);
    DB::table('configuracoes')->insert(['chave' => 'empresa.imagem_og', 'valor' => "site/{$og}.jpg", 'grupo' => 'empresa', 'created_at' => now(), 'updated_at' => now()]);
    MidiaUso::query()->delete();

    $this->migracao->up();
    $this->migracao->up(); // de novo: não duplica

    $midias = Midia::query()->get()->keyBy('uuid');
    expect($midias->keys()->sort()->values()->all())->toBe(collect([$noTexto, $capaAntiga, $solta, $og, $banner])->sort()->values()->all())
        ->and($midias[$noTexto]->caminho)->toBe("blog/2026/11/{$noTexto}.webp")
        ->and($midias[$noTexto]->url())->toBe("/storage/blog/2026/11/{$noTexto}.webp")
        ->and($midias[$noTexto]->descricao)->toBe('Gráfico')
        ->and($midias[$capaAntiga]->perfil)->toBe('capa')
        ->and($midias[$capaAntiga]->descricao)->toBe('A capa de antes')
        ->and($midias[$og]->perfil)->toBe('capa')
        ->and($midias[$banner]->perfil)->toBe('lancamento')
        ->and(MidiaUso::query()->count())->toBe(4);

    $this->travel(3)->hours();
    $this->artisan('midia:faxinar', ['--executar' => true])->assertSuccessful();
    expect(Midia::query()->whereNotNull('quarentena_em')->pluck('uuid')->all())->toBe([$solta]);
    Storage::disk('public')->assertExists("blog/2026/11/{$noTexto}.webp");
    Storage::disk('public')->assertExists("site/{$og}.jpg");

    // down(): tira as linhas, nunca os arquivos.
    $this->migracao->down();
    expect(Midia::query()->count())->toBe(0);
    Storage::disk('public')->assertExists("site/{$banner}.jpg");
});
