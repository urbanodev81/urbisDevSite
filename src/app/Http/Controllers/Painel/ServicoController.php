<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Servico;
use App\Support\ServicosDoSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Site › Serviços: as frentes de "O que fazemos" (bloco 4b da 246, 06/11/2026).
 *
 * Uma lista curta e ordenada. Ocultar (`ativo`) tira do site sem perder o
 * texto; remover apaga de vez (fica a linha "removido" na trilha). A ordem
 * muda pelos botões Subir/Descer, que trocam a posição com o vizinho.
 */
class ServicoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Painel/Servicos/Index', [
            'servicos' => Servico::query()->orderBy('ordem')->orderBy('id')->get()
                ->map(fn (Servico $s) => $s->only([
                    'id', 'titulo', 'frente', 'texto', 'icone', 'cor', 'situacao',
                    'link_url', 'link_rotulo', 'nome_no_catalogo', 'ativo',
                ]))->all(),
            ...ServicosDoSite::opcoes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Servico::create([...$this->validar($request), 'ordem' => (int) Servico::max('ordem') + 1]);

        return back()->with('sucesso', 'Serviço adicionado. Ele já está na home.');
    }

    public function update(Request $request, Servico $servico): RedirectResponse
    {
        $servico->update($this->validar($request));

        return back()->with('sucesso', 'Serviço salvo. A home já mostra o novo texto.');
    }

    public function destroy(Servico $servico): RedirectResponse
    {
        $servico->delete();

        return back()->with('sucesso', 'Serviço removido do site.');
    }

    /** Troca de lugar com o vizinho de cima (`subir`) ou de baixo (`descer`). */
    public function mover(Request $request, Servico $servico): RedirectResponse
    {
        $direcao = $request->validate(['direcao' => ['required', Rule::in(['subir', 'descer'])]])['direcao'];

        $lista = Servico::query()->orderBy('ordem')->orderBy('id')->get();
        $i = $lista->search(fn (Servico $s) => $s->is($servico));
        $j = $direcao === 'subir' ? $i - 1 : $i + 1;

        if ($i === false || ! $lista->has($j)) {
            return back();
        }

        // Renumera tudo (1, 2, 3…) com os dois trocados: `ordem` repetida, que
        // a semente ou uma edição antiga podem ter deixado, deixa de importar.
        $nova = $lista->all();
        [$nova[$i], $nova[$j]] = [$nova[$j], $nova[$i]];

        DB::transaction(function () use ($nova) {
            foreach (array_values($nova) as $posicao => $s) {
                if ($s->ordem !== $posicao + 1) {
                    $s->update(['ordem' => $posicao + 1]);
                }
            }
        });

        return back();
    }

    /** @return array<string, mixed> */
    private function validar(Request $request): array
    {
        $dados = $request->validate([
            'titulo' => ['required', 'string', 'max:80'],
            'frente' => ['required', 'string', 'max:40'],
            'texto' => ['required', 'string', 'max:400'],
            'icone' => ['required', Rule::in(array_keys(ServicosDoSite::ICONES))],
            'cor' => ['required', Rule::in(array_keys(ServicosDoSite::CORES))],
            'situacao' => ['required', Rule::in(array_keys(ServicosDoSite::SITUACOES))],
            'link_url' => ['nullable', 'url:https', 'max:255'],
            'link_rotulo' => ['nullable', 'required_with:link_url', 'string', 'max:40'],
            'nome_no_catalogo' => ['nullable', 'string', 'max:120'],
            'ativo' => ['boolean'],
        ], [
            'link_url.url' => 'Use o endereço completo, começando por https://.',
            'link_rotulo.required_with' => 'Com link, escreva também o texto do link.',
            'icone.in' => 'Escolha um dos ícones da lista.',
            'cor.in' => 'Escolha uma das cores da lista.',
            '*.max' => 'Passou do limite de :max letras.',
        ]);

        // Sem link, o rótulo dele não tem onde aparecer.
        if (empty($dados['link_url'])) {
            $dados['link_url'] = null;
            $dados['link_rotulo'] = null;
        }

        return $dados;
    }
}
