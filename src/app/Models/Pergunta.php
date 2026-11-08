<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Auditoria\Auditable;
use App\Support\PerguntasDoSite;
use Illuminate\Database\Eloquent\Model;

/**
 * Uma pergunta de "Como trabalhamos" (bloco 4d da 246, 08/11/2026).
 *
 * **Nenhuma view lê esta tabela direto.** Quem lê é `App\Support\PerguntasDoSite`,
 * que guarda no cache e devolve as 7 de sempre quando o banco está fora.
 * Toda gravação limpa esse cache (`booted`), então o site muda no mesmo segundo.
 */
class Pergunta extends Model
{
    use Auditable;

    protected $table = 'perguntas';

    protected $guarded = ['id'];

    protected $attributes = ['ativo' => true];

    protected function casts(): array
    {
        return ['ativo' => 'boolean', 'ordem' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => PerguntasDoSite::esquecer());
        static::deleted(fn () => PerguntasDoSite::esquecer());
    }
}
