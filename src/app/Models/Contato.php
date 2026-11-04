<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusContato;
use App\Support\Auditoria\Auditable;
use App\Support\TemUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UrbanoDev\Blog\Blog;

/**
 * Um contato que chegou pelo site: o formulário da home ou a porta de uma
 * demonstração (04/11/2026, bloco 2 da 246).
 *
 * ⚠️ **Dado pessoal de terceiro.** Retenção em `politicas_retencao` (recurso
 * `contato`) e expurgo agendado; o expurgo apaga de verdade, inclusive o que
 * foi arquivado. Na trilha, a criação entra sem campo nenhum (o dado pessoal
 * não é copiado para lá); o que se registra é quem mudou status e nota.
 */
class Contato extends Model
{
    use Auditable;
    use SoftDeletes;
    use TemUuid;

    public const ORIGEM_CONTATO = 'contato';

    public const ORIGEM_DEMONSTRACAO = 'demonstracao';

    protected $table = 'contatos';

    protected $guarded = ['id'];

    protected $attributes = ['status' => 'novo'];

    protected function casts(): array
    {
        return [
            'status' => StatusContato::class,
            'consentimento_em' => 'datetime',
            'email_enviado_em' => 'datetime',
        ];
    }

    public function scopeNovos(Builder $query): Builder
    {
        return $query->where('status', StatusContato::Novo->value);
    }

    /** Há quantos dias está esperando. A fila ordena por isso: quem espera mais se perde primeiro. */
    public function diasEsperando(): int
    {
        return (int) $this->created_at->startOfDay()->diffInDays(now()->startOfDay());
    }

    public function origemLegivel(): string
    {
        if ($this->origem === self::ORIGEM_DEMONSTRACAO) {
            return 'Demonstração'.($this->demo ? ': '.(config('demonstracoes.'.$this->demo.'.titulo') ?? $this->demo) : '');
        }

        return match (true) {
            $this->demo !== null => 'Formulário, vindo da demonstração '.(config('demonstracoes.'.$this->demo.'.titulo') ?? $this->demo),
            $this->texto !== null => 'Formulário, vindo do texto "'.(app(Blog::class)->achar($this->texto)?->titulo ?? $this->texto).'"',
            default => 'Formulário do site',
        };
    }

    /** O e-mail de aviso saiu? Sem isso, o lead está guardado, mas ninguém foi avisado. */
    public function emailFalhou(): bool
    {
        return $this->email_enviado_em === null && $this->email_erro !== null;
    }
}
