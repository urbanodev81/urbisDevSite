<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Lancamento;
use App\Support\ImagemDoSite;
use App\Support\LancamentosDoSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * Site › Lançamentos: os slides do banner logo depois do hero (bloco 4c da
 * 246, 06/11/2026). Mesmo desenho de Site › Serviços: um formulário para
 * adicionar e editar, Ocultar/Mostrar, Subir/Descer e remover.
 *
 * A imagem vem no mesmo formulário (multipart) e é recortada no centro em
 * 1200×750 por `ImagemDoSite`. Obrigatória ao adicionar; ao editar, sem
 * arquivo novo fica a de antes. Trocar ou remover apaga a anterior do disco
 * (as duas capturas de `public/lancamentos/` nunca são apagadas).
 */
class LancamentoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Painel/Lancamentos/Index', [
            'lancamentos' => Lancamento::query()->orderBy('ordem')->orderBy('id')->get()
                ->map(fn (Lancamento $l) => [
                    ...$l->only([
                        'id', 'etiqueta', 'titulo', 'texto', 'url', 'chamada',
                        'secundario_url', 'secundario', 'nota', 'ativo',
                    ]),
                    'imagem_url' => LancamentosDoSite::url($l->imagem),
                ])->all(),
            'medida' => LancamentosDoSite::MEDIDA,
        ]);
    }

    public function store(Request $request, ImagemDoSite $imagens): RedirectResponse
    {
        $dados = $this->validar($request, novo: true);
        $dados['imagem'] = $this->recortar($request, $imagens);

        Lancamento::create([...$dados, 'ordem' => (int) Lancamento::max('ordem') + 1]);

        return back()->with('sucesso', 'Lançamento adicionado. Ele já está no banner da home.');
    }

    public function update(Request $request, Lancamento $lancamento, ImagemDoSite $imagens): RedirectResponse
    {
        $dados = $this->validar($request, novo: false);

        $anterior = null;
        if ($request->hasFile('arquivo')) {
            $anterior = $lancamento->imagem;
            $dados['imagem'] = $this->recortar($request, $imagens);
        }

        $lancamento->update($dados);
        if ($anterior !== null && $anterior !== $lancamento->imagem) {
            LancamentosDoSite::apagarImagem($anterior);
        }

        return back()->with('sucesso', 'Lançamento salvo. A home já mostra a nova versão.');
    }

    public function destroy(Lancamento $lancamento): RedirectResponse
    {
        $lancamento->delete();

        return back()->with('sucesso', 'Lançamento removido do site.');
    }

    /** Troca de lugar com o vizinho de cima (`subir`) ou de baixo (`descer`). */
    public function mover(Request $request, Lancamento $lancamento): RedirectResponse
    {
        $direcao = $request->validate(['direcao' => ['required', Rule::in(['subir', 'descer'])]])['direcao'];

        $lista = Lancamento::query()->orderBy('ordem')->orderBy('id')->get();
        $i = $lista->search(fn (Lancamento $l) => $l->is($lancamento));
        $j = $direcao === 'subir' ? $i - 1 : $i + 1;

        if ($i === false || ! $lista->has($j)) {
            return back();
        }

        $nova = $lista->all();
        [$nova[$i], $nova[$j]] = [$nova[$j], $nova[$i]];

        DB::transaction(function () use ($nova) {
            foreach (array_values($nova) as $posicao => $l) {
                if ($l->ordem !== $posicao + 1) {
                    $l->update(['ordem' => $posicao + 1]);
                }
            }
        });

        return back();
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, bool $novo): array
    {
        [$largura, $altura] = LancamentosDoSite::MEDIDA;

        $dados = $request->validate([
            'etiqueta' => ['required', 'string', 'max:60'],
            'titulo' => ['required', 'string', 'max:120'],
            'texto' => ['required', 'string', 'max:400'],
            'url' => ['required', 'url:https', 'max:255'],
            'chamada' => ['required', 'string', 'max:40'],
            'secundario_url' => ['nullable', 'url:https', 'max:255'],
            'secundario' => ['nullable', 'required_with:secundario_url', 'string', 'max:40'],
            'nota' => ['nullable', 'string', 'max:120'],
            'ativo' => ['boolean'],
            'arquivo' => [$novo ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192',
                "dimensions:min_width={$largura},min_height={$altura},max_width=8000,max_height=8000"],
        ], [
            'url.url' => 'Use o endereço completo, começando por https://.',
            'secundario_url.url' => 'Use o endereço completo, começando por https://.',
            'secundario.required_with' => 'Com o segundo link, escreva também o texto dele.',
            'arquivo.required' => 'Envie a imagem do slide.',
            'arquivo.mimes' => 'Envie uma imagem em JPG, PNG ou WebP.',
            'arquivo.max' => 'A imagem passa de 8 MB.',
            'arquivo.dimensions' => "A imagem precisa ter pelo menos {$largura}×{$altura}.",
            '*.max' => 'Passou do limite de :max letras.',
        ]);
        unset($dados['arquivo']);

        // Sem o segundo link, o texto dele não tem onde aparecer.
        if (empty($dados['secundario_url'])) {
            $dados['secundario_url'] = null;
            $dados['secundario'] = null;
        }
        $dados['nota'] = ($dados['nota'] ?? null) ?: null;

        return $dados;
    }

    private function recortar(Request $request, ImagemDoSite $imagens): string
    {
        try {
            return $imagens->recortar($request->file('arquivo'), ...LancamentosDoSite::MEDIDA);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['arquivo' => $e->getMessage()]);
        }
    }
}
