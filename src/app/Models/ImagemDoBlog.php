<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\TemUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Imagem enviada pelo painel para um texto do blog (capa ou no meio do texto).
 * O arquivo é um WebP reencodado (sem EXIF) no disco `public`; a linha serve
 * para a galeria e para a faxina de imagem que nenhum texto usa.
 */
class ImagemDoBlog extends Model
{
    use TemUuid;

    protected $table = 'blog_imagens';

    protected $guarded = ['id'];

    public function url(): string
    {
        return '/storage/'.$this->caminho;
    }
}
