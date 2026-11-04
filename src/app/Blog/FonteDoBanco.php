<?php

declare(strict_types=1);

namespace App\Blog;

use App\Models\Texto;
use UrbanoDev\Blog\Fonte;

/**
 * Os textos escritos no painel (bloco 3a da 246). Vem antes da fonte de
 * arquivos em `config/blog.php`: o mesmo slug nas duas fica com o banco. Se o
 * banco cair, o pacote registra no log e segue com os `.md`. As páginas
 * públicas não dependem do banco para ficar de pé.
 *
 * O pacote decide o que está no ar pela data e pelo rascunho, como faz com os
 * arquivos. Daqui saem todos os que não foram arquivados.
 */
class FonteDoBanco implements Fonte
{
    public function ler(): array
    {
        return [
            'posts' => Texto::query()->get()->map(fn (Texto $t) => $t->paraPost())->all(),
            'erros' => [],
        ];
    }
}
