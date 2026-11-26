<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Auditoria\Auditable;
use App\Support\Financeiro\Periodicidade;
use App\Support\Financeiro\Sentido;
use App\Support\Financeiro\TipoDeCompromisso;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Uma coisa que vence (Financeiro, 26/11/2026): hospedagem, domínio,
 * mensalidade de cliente, lembrete.
 *
 * **Nunca se apaga.** Sai de cena por `arquivado_em`, e o pagamento lançado
 * errado é estornado. As travas do aviso (`aviso_*`) são gravadas por
 * `update()` direto na consulta, de propósito: elas mudam todo dia e não são
 * alteração de ninguém, então não vão para a trilha.
 */
class Compromisso extends Model
{
    use Auditable;

    protected $table = 'compromissos';

    protected $fillable = [
        'tipo', 'sentido', 'nome', 'contraparte', 'valor_centavos', 'periodicidade',
        'proximo_vencimento', 'avisar_dias_antes', 'observacao',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoDeCompromisso::class,
            'sentido' => Sentido::class,
            'periodicidade' => Periodicidade::class,
            'valor_centavos' => 'integer',
            'avisar_dias_antes' => 'integer',
            'proximo_vencimento' => 'immutable_date',
            'aviso_enviado_para' => 'immutable_date',
            'aviso_atraso_em' => 'immutable_date',
            'arquivado_em' => 'immutable_datetime',
        ];
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(PagamentoDeCompromisso::class)->orderByDesc('pago_em')->orderByDesc('id');
    }

    /** @param Builder<Compromisso> $q */
    public function scopeAtivos(Builder $q): void
    {
        $q->whereNull('arquivado_em');
    }

    public function arquivado(): bool
    {
        return $this->arquivado_em !== null;
    }

    /** Dias até o vencimento: negativo = atrasado, 0 = hoje. */
    public function diasAteVencer(?CarbonImmutable $hoje = null): int
    {
        $hoje = ($hoje ?? CarbonImmutable::today())->startOfDay();

        return (int) $hoje->diffInDays($this->proximo_vencimento->startOfDay(), false);
    }

    /**
     * A situação que a tela e o aviso mostram. Calculada, nunca gravada:
     * gravar daria uma coluna que mente no dia seguinte.
     *
     * @return array{chave: string, texto: string}
     */
    public function situacao(?CarbonImmutable $hoje = null): array
    {
        if ($this->arquivado()) {
            return ['chave' => 'arquivado', 'texto' => 'Arquivado'];
        }

        $dias = $this->diasAteVencer($hoje);

        return match (true) {
            $dias < 0 => ['chave' => 'atrasado', 'texto' => 'Atrasado '.abs($dias).(abs($dias) === 1 ? ' dia' : ' dias')],
            $dias === 0 => ['chave' => 'hoje', 'texto' => 'Vence hoje'],
            $dias <= $this->avisar_dias_antes => ['chave' => 'perto', 'texto' => 'Vence em '.$dias.($dias === 1 ? ' dia' : ' dias')],
            default => ['chave' => 'em_dia', 'texto' => 'Em dia'],
        };
    }

    /**
     * Registra o pagamento do vencimento atual e empurra o próximo.
     * Na `unica` (e no lembrete feito), o compromisso vai para arquivado.
     *
     * @param  array{valor_centavos: ?int, pago_em: string, forma: ?string, observacao: ?string}  $dados
     */
    public function registrarPagamento(array $dados): PagamentoDeCompromisso
    {
        return DB::transaction(function () use ($dados) {
            $pagamento = $this->pagamentos()->create([
                ...$dados,
                'referente_a' => $this->proximo_vencimento->toDateString(),
            ]);

            $seguinte = $this->periodicidade->seguinte($this->proximo_vencimento);

            if ($seguinte === null) {
                $this->arquivado_em = now()->toImmutable();
            } else {
                $this->proximo_vencimento = $seguinte;
            }
            $this->save();
            $this->zerarAvisos();

            return $pagamento;
        });
    }

    /**
     * Estorno: o pagamento fica no histórico, riscado, e o vencimento volta
     * para o que ele tinha quitado. Só o ÚLTIMO pagamento válido se estorna,
     * senão o vencimento voltaria para o meio da fila.
     */
    public function estornar(PagamentoDeCompromisso $pagamento): bool
    {
        $ultimo = $this->pagamentos()->whereNull('estornado_em')->first();

        if ($ultimo === null || ! $ultimo->is($pagamento)) {
            return false;
        }

        DB::transaction(function () use ($pagamento) {
            $pagamento->update(['estornado_em' => now()]);
            $this->proximo_vencimento = $pagamento->referente_a;
            $this->arquivado_em = null;
            $this->save();
            $this->zerarAvisos();
        });

        return true;
    }

    public function zerarAvisos(): void
    {
        static::query()->whereKey($this->getKey())->update(['aviso_enviado_para' => null, 'aviso_atraso_em' => null]);
        $this->aviso_enviado_para = null;
        $this->aviso_atraso_em = null;
        $this->syncOriginalAttributes(['aviso_enviado_para', 'aviso_atraso_em']);
    }
}
