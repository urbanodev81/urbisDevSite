<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Uma chave de configuração do site.
 *
 * **Nunca leia esta tabela direto de uma tela.** Quem lê é
 * `App\Support\Configuracao` (o catálogo), que devolve o valor já com default,
 * já validado contra a lista do que existe. É a "regra de ouro" do
 * PADROES.md §Site público: catálogo em UM arquivo, e a validação sai dele.
 *
 * O nome desta classe é `ConfiguracaoRegistro` no uso, importado com alias, e
 * o catálogo fica com o nome curto — quem escreve tela quer o catálogo.
 */
class Configuracao extends Model
{
    protected $table = 'configuracoes';

    protected $guarded = ['id'];

    public $timestamps = true;
}
