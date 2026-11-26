<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Compromisso;
use App\Models\PagamentoDeCompromisso;
use App\Support\Financeiro\Dinheiro;
use App\Support\Financeiro\FormaDePagamento;
use App\Support\Financeiro\Periodicidade;
use App\Support\Financeiro\Sentido;
use App\Support\Financeiro\TipoDeCompromisso;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Financeiro (26/11/2026): o que vence, quanto, de quanto em quanto tempo, e
 * o histórico de cada pagamento. Desenho em
 * `Arquivos/analise/em-voo/urbisdev-financeiro.md`.
 *
 * Nada se apaga daqui: arquivar tira dos avisos e da lista principal;
 * pagamento errado se estorna. O aviso diário é o `financeiro:avisar`.
 */
class FinanceiroController extends Controller
{
    public function index(): Response
    {
        $hoje = CarbonImmutable::today();
        $todos = Compromisso::query()->with('pagamentos')->orderBy('proximo_vencimento')->orderBy('id')->get();
        $ativos = $todos->filter(fn (Compromisso $c) => ! $c->arquivado());

        // "Por mês": cada recorrente dividido pelos meses do período. A uma
        // vez só não entra (não é custo de todo mês).
        $porMes = fn (Sentido $s) => (int) round($ativos
            ->filter(fn (Compromisso $c) => $c->sentido === $s && $c->valor_centavos && $c->periodicidade->meses())
            ->sum(fn (Compromisso $c) => $c->valor_centavos / $c->periodicidade->meses()));

        $em30 = $ativos->filter(fn (Compromisso $c) => $c->diasAteVencer($hoje) <= 30);

        return Inertia::render('Painel/Financeiro/Index', [
            'compromissos' => $todos->map(fn (Compromisso $c) => $this->linha($c, $hoje))->values()->all(),
            'totais' => [
                'pagarPorMes' => Dinheiro::formatar($porMes(Sentido::Pagar)),
                'receberPorMes' => Dinheiro::formatar($porMes(Sentido::Receber)),
                'atrasados' => $ativos->filter(fn (Compromisso $c) => $c->diasAteVencer($hoje) < 0)->count(),
                'pagar30' => Dinheiro::formatar((int) $em30->where('sentido', Sentido::Pagar)->sum('valor_centavos')),
                'receber30' => Dinheiro::formatar((int) $em30->where('sentido', Sentido::Receber)->sum('valor_centavos')),
            ],
            'opcoes' => [
                'tipos' => TipoDeCompromisso::opcoes(),
                'sentidos' => Sentido::opcoes(),
                'periodicidades' => Periodicidade::opcoes(),
                'formas' => FormaDePagamento::opcoes(),
            ],
            'hoje' => $hoje->toDateString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Compromisso::create($this->validar($request));

        return back()->with('sucesso', 'Compromisso salvo. O aviso sai pelo Telegram quando entrar na janela.');
    }

    public function update(Request $request, Compromisso $compromisso): RedirectResponse
    {
        $antes = $compromisso->proximo_vencimento;
        $compromisso->update($this->validar($request));

        // Mudou a data à mão: o aviso vale de novo para a data nova.
        if (! $compromisso->proximo_vencimento->isSameDay($antes)) {
            $compromisso->zerarAvisos();
        }

        return back()->with('sucesso', 'Compromisso salvo.');
    }

    public function arquivar(Compromisso $compromisso): RedirectResponse
    {
        $compromisso->arquivado_em = now()->toImmutable();
        $compromisso->save();

        return back()->with('sucesso', 'Arquivado. Não avisa mais; o histórico continua aqui.');
    }

    public function reativar(Compromisso $compromisso): RedirectResponse
    {
        $compromisso->arquivado_em = null;
        $compromisso->save();
        $compromisso->zerarAvisos();

        return back()->with('sucesso', 'Reativado. Confira a data do próximo vencimento.');
    }

    public function pagar(Request $request, Compromisso $compromisso): RedirectResponse
    {
        abort_if($compromisso->arquivado(), 422, 'Compromisso arquivado.');

        $dados = $request->validate([
            'valor' => ['nullable', 'string', 'max:20'],
            'pago_em' => ['required', 'date', 'before_or_equal:today'],
            'forma' => ['nullable', Rule::enum(FormaDePagamento::class)],
            'observacao' => ['nullable', 'string', 'max:300'],
        ]);

        $referente = $compromisso->proximo_vencimento->format('d/m/Y');
        $compromisso->registrarPagamento([
            'valor_centavos' => $this->centavos($dados['valor'] ?? null, 'valor'),
            'pago_em' => $dados['pago_em'],
            'forma' => $dados['forma'] ?? null,
            'observacao' => ($dados['observacao'] ?? null) ?: null,
        ]);

        return back()->with('sucesso', $compromisso->arquivado()
            ? "Pagamento de {$referente} registrado. Era uma vez só: foi para os arquivados."
            : "Pagamento de {$referente} registrado. Próximo vencimento: {$compromisso->proximo_vencimento->format('d/m/Y')}.");
    }

    public function estornar(Compromisso $compromisso, PagamentoDeCompromisso $pagamento): RedirectResponse
    {
        abort_unless($pagamento->compromisso_id === $compromisso->id, 404);

        if (! $compromisso->estornar($pagamento)) {
            return back()->with('erro', 'Só dá para estornar o último pagamento válido.');
        }

        return back()->with('sucesso', "Pagamento estornado. O vencimento voltou para {$compromisso->proximo_vencimento->format('d/m/Y')}.");
    }

    /** @return array<string, mixed> */
    private function linha(Compromisso $c, CarbonImmutable $hoje): array
    {
        return [
            'id' => $c->id,
            'tipo' => $c->tipo->value,
            'tipoRotulo' => $c->tipo->rotulo(),
            'sentido' => $c->sentido->value,
            'sentidoRotulo' => $c->sentido->rotulo(),
            'nome' => $c->nome,
            'contraparte' => $c->contraparte,
            'valor' => $c->valor_centavos !== null ? number_format($c->valor_centavos / 100, 2, ',', '.') : '',
            'valorFormatado' => Dinheiro::formatar($c->valor_centavos),
            'periodicidade' => $c->periodicidade->value,
            'periodicidadeRotulo' => $c->periodicidade->rotulo(),
            'proximoVencimento' => $c->proximo_vencimento->toDateString(),
            'proximoVencimentoFormatado' => $c->proximo_vencimento->format('d/m/Y'),
            'avisarDiasAntes' => $c->avisar_dias_antes,
            'observacao' => $c->observacao,
            'arquivado' => $c->arquivado(),
            'situacao' => $c->situacao($hoje),
            'dias' => $c->diasAteVencer($hoje),
            'pagamentos' => $c->pagamentos->map(fn (PagamentoDeCompromisso $p) => [
                'id' => $p->id,
                'referenteA' => $p->referente_a->format('d/m/Y'),
                'pagoEm' => $p->pago_em->format('d/m/Y'),
                'valor' => Dinheiro::formatar($p->valor_centavos),
                'forma' => $p->forma?->rotulo(),
                'observacao' => $p->observacao,
                'estornado' => $p->estornado_em !== null,
            ])->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function validar(Request $request): array
    {
        $dados = $request->validate([
            'tipo' => ['required', Rule::enum(TipoDeCompromisso::class)],
            'sentido' => ['required', Rule::enum(Sentido::class)],
            'nome' => ['required', 'string', 'max:120'],
            'contraparte' => ['nullable', 'string', 'max:120'],
            'valor' => ['nullable', 'string', 'max:20'],
            'periodicidade' => ['required', Rule::enum(Periodicidade::class)],
            'proximo_vencimento' => ['required', 'date'],
            'avisar_dias_antes' => ['required', 'integer', 'min:0', 'max:90'],
            'observacao' => ['nullable', 'string', 'max:2000'],
        ]);

        $dados['valor_centavos'] = $this->centavos($dados['valor'] ?? null, 'valor');
        unset($dados['valor']);
        $dados['contraparte'] = ($dados['contraparte'] ?? null) ?: null;
        $dados['observacao'] = ($dados['observacao'] ?? null) ?: null;

        return $dados;
    }

    private function centavos(?string $texto, string $campo): ?int
    {
        $centavos = Dinheiro::centavos($texto);

        if ($centavos === false) {
            throw ValidationException::withMessages([$campo => 'Escreva o valor como 1.234,56.']);
        }

        return $centavos;
    }
}
