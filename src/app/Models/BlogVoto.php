<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** "Este texto ajudou?" (bloco 3b da 246). Anônimo: sim/não e, opcional, o que faltou. */
class BlogVoto extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'blog_votos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['util' => 'boolean', 'created_at' => 'immutable_datetime'];
    }
}
