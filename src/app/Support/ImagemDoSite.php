<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Imagem que o painel sobe para o site (OG no 4a; slides de lançamento no 4c).
 *
 * Recorta o maior retângulo com a proporção pedida, no centro, e grava em
 * **JPEG**, não WebP como as do blog: é o que todo robô de prévia de link lê
 * (WhatsApp e LinkedIn incluídos). Fica em `storage/app/public/site/`, que o
 * backup da VPS precisa levar junto com o `blog/`.
 */
class ImagemDoSite
{
    private const QUALIDADE = 85;

    private const PIXELS_MAXIMOS = 25_000_000;

    /** @return string caminho no disco `public` */
    public function recortar(UploadedFile $arquivo, int $largura, int $altura): string
    {
        $medidas = @getimagesize($arquivo->getRealPath());
        if ($medidas === false || $medidas[0] * $medidas[1] > self::PIXELS_MAXIMOS) {
            throw new RuntimeException('A imagem é grande demais (acima de 25 megapixels). Reduza e envie de novo.');
        }
        ini_set('memory_limit', '384M');

        $origem = @imagecreatefromstring((string) file_get_contents($arquivo->getRealPath()));
        if ($origem === false) {
            throw new RuntimeException('Não deu para abrir a imagem.');
        }

        [$w, $h] = [imagesx($origem), imagesy($origem)];
        $escala = min($w / $largura, $h / $altura);
        [$cw, $ch] = [(int) round($largura * $escala), (int) round($altura * $escala)];

        $nova = imagecreatetruecolor($largura, $altura);
        // Fundo branco: PNG com transparência viraria preto no JPEG.
        imagefill($nova, 0, 0, (int) imagecolorallocate($nova, 255, 255, 255));
        imagecopyresampled($nova, $origem, 0, 0, intdiv($w - $cw, 2), intdiv($h - $ch, 2), $largura, $altura, $cw, $ch);

        ob_start();
        imagejpeg($nova, null, self::QUALIDADE);
        $bytes = (string) ob_get_clean();

        $caminho = ConfiguracaoDoSite::PASTA_IMAGENS.'/'.Str::uuid7().'.jpg';
        Storage::disk('public')->put($caminho, $bytes);

        return $caminho;
    }
}
