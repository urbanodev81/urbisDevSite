<?php

declare(strict_types=1);

namespace App\Blog;

use App\Models\ImagemDoBlog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Imagens do blog enviadas pelo painel (bloco 3a da 246).
 *
 * Nenhuma imagem é publicada como chegou: o GD abre, reduz e grava um WebP
 * novo. Isso tira o EXIF (o GPS da foto do celular, o modelo do aparelho) e
 * qualquer coisa escondida no arquivo, e o nome passa a ser um uuid (sem o
 * nome original na URL).
 *
 * - no meio do texto: até 1600 px no lado maior, sem aumentar;
 * - capa: recorte no centro em 1200×630, o tamanho da prévia nas redes.
 */
class GuardaImagens
{
    public const LADO_MAXIMO = 1600;

    public const CAPA = [1200, 630];

    private const QUALIDADE = 82;

    /** 25 MP: o GD descomprime 4 bytes por pixel (~100 MB). Acima disso, foto-bomba derrubaria o PHP. */
    public const PIXELS_MAXIMOS = 25_000_000;

    public function guardar(UploadedFile $arquivo, bool $capa, ?int $userId): ImagemDoBlog
    {
        $medidas = @getimagesize($arquivo->getRealPath());
        if ($medidas === false || $medidas[0] * $medidas[1] > self::PIXELS_MAXIMOS) {
            throw new RuntimeException('A imagem é grande demais (acima de 25 megapixels). Reduza e envie de novo.');
        }
        // Original + reduzida + a girada cabem com folga; o limite padrão (128 MB) não.
        ini_set('memory_limit', '384M');

        $origem = @imagecreatefromstring((string) file_get_contents($arquivo->getRealPath()));
        if ($origem === false) {
            throw new RuntimeException('Não deu para abrir a imagem.');
        }
        $origem = $this->endireitar($origem, $arquivo->getRealPath());

        [$w, $h] = [imagesx($origem), imagesy($origem)];
        if ($capa) {
            [$nw, $nh] = self::CAPA;
            // Maior recorte com a proporção da capa, no centro da foto.
            $escala = min($w / $nw, $h / $nh);
            [$cw, $ch] = [(int) round($nw * $escala), (int) round($nh * $escala)];
            [$cx, $cy] = [intdiv($w - $cw, 2), intdiv($h - $ch, 2)];
        } else {
            $fator = min(1, self::LADO_MAXIMO / max($w, $h));
            [$nw, $nh] = [max(1, (int) round($w * $fator)), max(1, (int) round($h * $fator))];
            [$cx, $cy, $cw, $ch] = [0, 0, $w, $h];
        }

        $nova = imagecreatetruecolor($nw, $nh);
        imagealphablending($nova, false);
        imagesavealpha($nova, true);
        imagecopyresampled($nova, $origem, 0, 0, $cx, $cy, $nw, $nh, $cw, $ch);

        ob_start();
        imagewebp($nova, null, self::QUALIDADE);
        $bytes = (string) ob_get_clean();

        $uuid = (string) Str::uuid7();
        $caminho = 'blog/'.now()->format('Y/m').'/'.$uuid.'.webp';
        Storage::disk('public')->put($caminho, $bytes);

        return ImagemDoBlog::create([
            'uuid' => $uuid,
            'caminho' => $caminho,
            'tipo' => $capa ? 'capa' : 'texto',
            'largura' => $nw,
            'altura' => $nh,
            'bytes' => strlen($bytes),
            'nome_original' => mb_substr($arquivo->getClientOriginalName(), 0, 255),
            'user_id' => $userId,
        ]);
    }

    /** Foto de celular vem "deitada" com a orientação só no EXIF, que vamos jogar fora: gira antes. */
    private function endireitar(\GdImage $img, string $caminho): \GdImage
    {
        $orientacao = function_exists('exif_read_data') ? (@exif_read_data($caminho)['Orientation'] ?? 1) : 1;

        return match ((int) $orientacao) {
            3 => imagerotate($img, 180, 0) ?: $img,
            6 => imagerotate($img, -90, 0) ?: $img,
            8 => imagerotate($img, 90, 0) ?: $img,
            default => $img,
        };
    }
}
