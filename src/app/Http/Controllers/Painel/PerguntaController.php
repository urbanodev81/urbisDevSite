<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Pergunta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Site › Perguntas: as de "Como trabalhamos" (bloco 4d da 246, 08/11/2026).
 *
 * Mesmo desenho de Serviços: ocultar (`ativo`) tira do site sem perder o
 * texto; remover apaga de vez (fica a linha "removido" na trilha); Subir/Descer
 * trocam a posição com o vizinho.
 */
class PerguntaController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Painel/Perguntas/Index', [
            'perguntas' => Pergunta::query()->orderBy('ordem')->orderBy('id')->get()
                ->map(fn (Pergunta $p) => $p->only(['id', 'pergunta', 'explicacao', 'ativo']))->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Pergunta::create([...$this->validar($request), 'ordem' => (int) Pergunta::max('ordem') + 1]);

        return back()->with('sucesso', 'Pergunta adicionada. Ela já está em Como trabalhamos.');
    }

    public function update(Request $request, Pergunta $pergunta): RedirectResponse
    {
        $pergunta->update($this->validar($request));

        return back()->with('sucesso', 'Pergunta salva. A página já mostra o novo texto.');
    }

    public function destroy(Pergunta $pergunta): RedirectResponse
    {
        $pergunta->delete();

        return back()->with('sucesso', 'Pergunta removida do site.');
    }

    /** Troca de lugar com a vizinha de cima (`subir`) ou de baixo (`descer`). */
    public function mover(Request $request, Pergunta $pergunta): RedirectResponse
    {
        $direcao = $request->validate(['direcao' => ['required', Rule::in(['subir', 'descer'])]])['direcao'];

        $lista = Pergunta::query()->orderBy('ordem')->orderBy('id')->get();
        $i = $lista->search(fn (Pergunta $p) => $p->is($pergunta));
        $j = $direcao === 'subir' ? $i - 1 : $i + 1;

        if ($i === false || ! $lista->has($j)) {
            return back();
        }

        // Renumera tudo (1, 2, 3…) com as duas trocadas, como em Serviços.
        $nova = $lista->all();
        [$nova[$i], $nova[$j]] = [$nova[$j], $nova[$i]];

        DB::transaction(function () use ($nova) {
            foreach (array_values($nova) as $posicao => $p) {
                if ($p->ordem !== $posicao + 1) {
                    $p->update(['ordem' => $posicao + 1]);
                }
            }
        });

        return back();
    }

    /** @return array<string, mixed> */
    private function validar(Request $request): array
    {
        $dados = $request->validate([
            'pergunta' => ['required', 'string', 'max:200'],
            'explicacao' => ['nullable', 'string', 'max:300'],
            'ativo' => ['boolean'],
        ], [
            '*.max' => 'Passou do limite de :max letras.',
        ]);

        $dados['explicacao'] = ($dados['explicacao'] ?? null) ?: null;

        return $dados;
    }
}
