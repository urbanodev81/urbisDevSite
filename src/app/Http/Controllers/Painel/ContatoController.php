<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Enums\StatusContato;
use App\Http\Controllers\Controller;
use App\Models\Contato;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Contatos do site (04/11/2026, bloco 2 da 246): quem escreveu pelo
 * formulário ou abriu uma demonstração.
 *
 * `leads.ver` abre; `leads.gerenciar` muda status e nota, arquiva e exporta.
 * Ninguém apaga daqui: arquivar esconde, e a exclusão real é só a do expurgo
 * por retenção (`lgpd:expurgo`).
 */
class ContatoController extends Controller
{
    public function index(Request $request): Response
    {
        $arquivados = $request->boolean('arquivados');
        $status = $request->string('status')->toString();
        $statusValido = StatusContato::tryFrom($status);

        $contatos = Contato::query()
            ->when($arquivados, fn ($q) => $q->onlyTrashed())
            ->when(! $arquivados && $statusValido !== null, fn ($q) => $q->where('status', $statusValido->value))
            // Sem filtro, a fila mostra quem ainda espera: novo primeiro, e
            // dentro dele o mais antigo primeiro (quem espera mais se perde).
            ->when(! $arquivados && $statusValido === null && $status !== 'todos', fn ($q) => $q->novos())
            ->orderBy('created_at', $arquivados || $status === 'todos' ? 'desc' : 'asc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Contato $c) => [
                'uuid' => $c->uuid,
                'nome' => $c->nome,
                'email' => $c->email,
                'telefone' => $c->telefone,
                'origem' => $c->origemLegivel(),
                'trecho' => $c->mensagem ? mb_strimwidth($c->mensagem, 0, 140, '…') : null,
                'status' => $this->status($c->status),
                'recebidoEm' => $c->created_at->format('d/m/Y H:i'),
                'diasEsperando' => $c->diasEsperando(),
                'emailFalhou' => $c->emailFalhou(),
            ]);

        return Inertia::render('Painel/Contatos/Index', [
            'contatos' => $contatos,
            'filtro' => $statusValido?->value ?? ($status === 'todos' ? 'todos' : 'novo'),
            'status' => StatusContato::opcoes(),
            'arquivados' => $arquivados,
            'totais' => [Contato::query()->count(), Contato::onlyTrashed()->count()],
            'resumo' => [
                'novos' => Contato::query()->novos()->count(),
                'semAviso' => Contato::query()->whereNull('email_enviado_em')->whereNotNull('email_erro')->count(),
            ],
        ]);
    }

    public function show(Request $request, string $contato): Response
    {
        $c = $this->achar($contato);

        return Inertia::render('Painel/Contatos/Detalhe', [
            'contato' => [
                'uuid' => $c->uuid,
                'nome' => $c->nome,
                'email' => $c->email,
                'telefone' => $c->telefone,
                // Só dígitos, com o 55 do Brasil quando faltar: o link do WhatsApp.
                'whatsapp' => $this->whatsapp($c->telefone),
                'mensagem' => $c->mensagem,
                'origem' => $c->origemLegivel(),
                'status' => $this->status($c->status),
                'nota' => $c->nota,
                'recebidoEm' => $c->created_at->format('d/m/Y H:i'),
                'diasEsperando' => $c->diasEsperando(),
                'consentimento' => $c->consentimento_em?->format('d/m/Y H:i'),
                'emailEnviadoEm' => $c->email_enviado_em?->format('d/m/Y H:i'),
                'emailFalhou' => $c->emailFalhou(),
                'arquivado' => $c->trashed(),
            ],
            'status' => StatusContato::opcoes(),
            'podeGerenciar' => $request->user()?->can('leads.gerenciar') ?? false,
        ]);
    }

    public function update(Request $request, Contato $contato): RedirectResponse
    {
        $dados = $request->validate([
            'status' => ['required', new Enum(StatusContato::class)],
            'nota' => ['nullable', 'string', 'max:5000'],
        ]);

        $contato->update($dados);

        return back()->with('sucesso', 'Contato atualizado.');
    }

    public function destroy(Contato $contato): RedirectResponse
    {
        $contato->delete();

        return redirect()->route('painel.contatos.index')->with('sucesso', 'Contato arquivado. Ele está na aba Arquivados.');
    }

    public function restaurar(string $contato): RedirectResponse
    {
        abort_unless(Str::isUuid($contato), 404);

        $c = Contato::onlyTrashed()->where('uuid', $contato)->firstOrFail();
        $c->restore();

        return redirect()->route('painel.contatos.show', $c)->with('sucesso', 'Contato restaurado.');
    }

    /**
     * LGPD art. 18, II: o titular pediu os dados dele. Sai tudo o que o site
     * guarda com aquele e-mail, inclusive o arquivado, num JSON para mandar.
     */
    public function exportar(string $contato): JsonResponse
    {
        $c = $this->achar($contato);

        $registros = Contato::withTrashed()
            ->whereRaw('lower(email) = ?', [mb_strtolower($c->email)])
            ->orderBy('created_at')
            ->get()
            ->map(fn (Contato $r) => [
                'recebido_em' => $r->created_at->toIso8601String(),
                'origem' => $r->origemLegivel(),
                'nome' => $r->nome,
                'email' => $r->email,
                'telefone' => $r->telefone,
                'mensagem' => $r->mensagem,
                'consentimento_em' => $r->consentimento_em?->toIso8601String(),
            ])->all();

        return response()->json([
            'controlador' => 'Urbano Dev — urbisdev.tech',
            'gerado_em' => now()->toIso8601String(),
            'prazo_de_guarda' => 'Contatos ficam guardados por até 24 meses e então são apagados.',
            'registros' => $registros,
        ], 200, [
            'Content-Disposition' => 'attachment; filename="dados-do-titular-'.now()->format('Y-m-d').'.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** O detalhe abre também o arquivado (para ler e restaurar). UUID torto = 404. */
    private function achar(string $uuid): Contato
    {
        abort_unless(Str::isUuid($uuid), 404);

        return Contato::withTrashed()->where('uuid', $uuid)->firstOrFail();
    }

    /** @return array{valor: string, rotulo: string, token: string, icone: string} */
    private function status(StatusContato $s): array
    {
        return ['valor' => $s->value, 'rotulo' => $s->rotulo(), 'token' => $s->token(), 'icone' => $s->icone()];
    }

    private function whatsapp(string $telefone): ?string
    {
        $digitos = preg_replace('/\D/', '', $telefone);

        if (strlen($digitos) < 10) {
            return null;
        }

        return strlen($digitos) <= 11 ? '55'.$digitos : $digitos;
    }
}
