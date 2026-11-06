<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Auditoria\Auditable;
use App\Support\LancamentosDoSite;
use Illuminate\Database\Eloquent\Model;

/**
 * Um slide do banner de lançamentos (bloco 4c da 246, 06/11/2026).
 *
 * **Nenhuma view lê esta tabela direto.** Quem lê é `App\Support\LancamentosDoSite`,
 * que guarda no cache e devolve os slides de sempre quando o banco está fora.
 * Toda gravação limpa esse cache (`booted`), e remover apaga a imagem que a
 * tela subiu.
 */
class Lancamento extends Model
{
    use Auditable;

    protected $table = 'lancamentos';

    protected $guarded = ['id'];

    protected $attributes = ['ativo' => true];

    protected function casts(): array
    {
        return ['ativo' => 'boolean', 'ordem' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => LancamentosDoSite::esquecer());
        static::deleted(function (Lancamento $lancamento) {
            LancamentosDoSite::apagarImagem($lancamento->imagem);
            LancamentosDoSite::esquecer();
        });
    }
}
