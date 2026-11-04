<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Por quanto tempo cada coisa fica guardada (veio do molde, Duo).
 *
 * Em tabela e não em `config/` porque precisa ser publicável: o titular tem
 * direito de saber o prazo. `nunca_expurgar` é coluna, não lista de nomes
 * (lição do USI). As linhas nascem em `Database\Seeders\PoliticasDeRetencao`.
 */
class PoliticaRetencao extends Model
{
    protected $table = 'politicas_retencao';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'meses' => 'integer',
            'nunca_expurgar' => 'boolean',
        ];
    }
}
