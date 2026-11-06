<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Auditoria\Auditable;
use App\Support\ServicosDoSite;
use Illuminate\Database\Eloquent\Model;

/**
 * Uma frente da seção "O que fazemos" (bloco 4b da 246, 06/11/2026).
 *
 * **Nenhuma view lê esta tabela direto.** Quem lê é `App\Support\ServicosDoSite`,
 * que guarda no cache e devolve a lista de sempre quando o banco está fora.
 * Toda gravação limpa esse cache (`booted`), então o site muda no mesmo segundo.
 */
class Servico extends Model
{
    use Auditable;

    protected $table = 'servicos';

    protected $guarded = ['id'];

    protected $attributes = ['situacao' => 'no_ar', 'ativo' => true];

    protected function casts(): array
    {
        return ['ativo' => 'boolean', 'ordem' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => ServicosDoSite::esquecer());
        static::deleted(fn () => ServicosDoSite::esquecer());
    }
}
