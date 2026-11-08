<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Auditoria\Auditable;
use App\Support\LancamentosDoSite;
use Illuminate\Database\Eloquent\Model;
use UrbanoDev\Midia\Concerns\UsaMidias;

/**
 * Um slide do banner de lançamentos (bloco 4c da 246, 06/11/2026).
 *
 * **Nenhuma view lê esta tabela direto.** Quem lê é `App\Support\LancamentosDoSite`,
 * que guarda no cache e devolve os slides de sempre quando o banco está fora.
 * Toda gravação limpa esse cache (`booted`) e registra o uso da imagem;
 * remover solta o uso (o arquivo vai para a quarentena pela faxina, nunca é apagado).
 */
class Lancamento extends Model
{
    use Auditable;
    use UsaMidias;

    protected $table = 'lancamentos';

    protected $guarded = ['id'];

    protected $attributes = ['ativo' => true];

    protected function casts(): array
    {
        return ['ativo' => 'boolean', 'ordem' => 'integer'];
    }

    protected static function booted(): void
    {
        // Uso da imagem no pacote urbanodev/midia (255): a captura antiga de
        // `public/lancamentos/` não tem uuid e fica de fora, como deve.
        static::saved(function (Lancamento $lancamento) {
            $lancamento->sincronizarMidias('imagem', [$lancamento->imagem]);
            LancamentosDoSite::esquecer();
        });
        static::deleted(fn () => LancamentosDoSite::esquecer());
    }

    public function rotuloParaMidia(string $campo): string
    {
        return 'Lançamento: '.$this->titulo;
    }
}
