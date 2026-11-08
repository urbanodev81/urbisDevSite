<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Auditoria\Auditable;
use UrbanoDev\Midia\Concerns\UsaMidias;
use Illuminate\Database\Eloquent\Model;

/**
 * Uma chave de configuração do site.
 *
 * **Nunca leia esta tabela direto de uma tela.** Quem lê é
 * `App\Support\ConfiguracaoDoSite` (o catálogo), que devolve o valor já com default,
 * já validado contra a lista do que existe. É a "regra de ouro" do
 * PADROES.md §Site público: catálogo em UM arquivo, e a validação sai dele.
 */
class Configuracao extends Model
{
    use Auditable;
    use UsaMidias;

    protected $table = 'configuracoes';

    protected $guarded = ['id'];

    public $timestamps = true;

    public function rotuloParaMidia(string $campo): string
    {
        return 'Configuração do site: '.(\App\Support\ConfiguracaoDoSite::CATALOGO[$this->chave]['rotulo'] ?? $this->chave);
    }
}
