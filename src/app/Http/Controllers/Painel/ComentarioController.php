<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\BlogComentario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use UrbanoDev\Blog\Blog;

/**
 * Moderação dos comentários do blog (04/11/2026, bloco 3b da 246).
 *
 * Sem filtro, a tela é a FILA: os que aguardam, do mais antigo ao mais novo.
 * Aprovar põe no ar; recusar tira (e o recusado expira pela retenção);
 * responder publica a resposta da equipe como "urbisDev" embaixo do
 * comentário. Ninguém apaga daqui: arquivar esconde.
 */
class ComentarioController extends Controller
{
    public function index(Request $request, Blog $blog): Response
    {
        $arquivados = $request->boolean('arquivados');
        $status = $request->string('status')->toString();
        $filtro = array_key_exists($status, BlogComentario::STATUS) ? $status : BlogComentario::PENDENTE;
        $titulos = collect($blog->publicados())->mapWithKeys(fn ($p) => [$p->slug => $p->titulo]);

        $comentarios = BlogComentario::query()
            ->when($arquivados, fn ($q) => $q->onlyTrashed(), fn ($q) => $q->where('status', $filtro))
            ->orderBy('created_at', ! $arquivados && $filtro === BlogComentario::PENDENTE ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (BlogComentario $c) => [
                'uuid' => $c->uuid,
                'nome' => $c->nome,
                'email' => $c->email,
                'linkedin' => $c->verificadoPeloLinkedin(),
                'corpo' => $c->corpo,
                'resposta' => $c->resposta,
                'status' => ['valor' => $c->status] + BlogComentario::STATUS[$c->status],
                'texto' => [
                    'slug' => $c->slug,
                    'titulo' => $titulos[$c->slug] ?? $c->slug,
                    'url' => isset($titulos[$c->slug]) ? url('/blog/'.$c->slug).'#comentarios' : null,
                ],
                'recebidoEm' => $c->created_at->format('d/m/Y H:i'),
                'moderado' => $c->moderado_em ? $c->moderado_em->format('d/m/Y H:i').' por '.($c->moderador?->name ?? '—') : null,
            ]);

        return Inertia::render('Painel/Blog/Comentarios', [
            'comentarios' => $comentarios,
            'filtro' => $filtro,
            'status' => collect(BlogComentario::STATUS)->map(fn ($s, $valor) => ['valor' => $valor, 'rotulo' => $s['rotulo']])->values(),
            'arquivados' => $arquivados,
            'totais' => [BlogComentario::query()->count(), BlogComentario::onlyTrashed()->count()],
            'pendentes' => BlogComentario::query()->pendentes()->count(),
        ]);
    }

    public function update(Request $request, BlogComentario $comentario): RedirectResponse
    {
        $dados = $request->validate([
            'status' => ['sometimes', 'required', Rule::in(array_keys(BlogComentario::STATUS))],
            'resposta' => ['sometimes', 'nullable', 'string', 'max:3000'],
        ]);

        if (array_key_exists('status', $dados) && $dados['status'] !== $comentario->status) {
            $comentario->fill([
                'status' => $dados['status'],
                'moderado_por' => $request->user()->id,
                'moderado_em' => now(),
            ]);
        }

        if (array_key_exists('resposta', $dados)) {
            $resposta = filled($dados['resposta']) ? trim(strip_tags($dados['resposta'])) : null;
            if ($resposta !== $comentario->resposta) {
                $comentario->fill([
                    'resposta' => $resposta,
                    'respondido_por' => $resposta ? $request->user()->id : null,
                    'respondido_em' => $resposta ? now() : null,
                ]);
            }
        }

        $comentario->save();

        return back()->with('sucesso', match (true) {
            ! array_key_exists('status', $dados) => 'Resposta salva.',
            $comentario->status === BlogComentario::APROVADO => 'Comentário aprovado. Já está no texto.',
            $comentario->status === BlogComentario::RECUSADO => 'Comentário recusado.',
            default => 'Comentário de volta à fila.',
        });
    }

    public function destroy(BlogComentario $comentario): RedirectResponse
    {
        $comentario->delete();

        return back()->with('sucesso', 'Comentário arquivado. Ele saiu do texto e está na aba Arquivados.');
    }

    public function restaurar(string $comentario): RedirectResponse
    {
        abort_unless(Str::isUuid($comentario), 404);
        BlogComentario::onlyTrashed()->where('uuid', $comentario)->firstOrFail()->restore();

        return back()->with('sucesso', 'Comentário restaurado.');
    }
}
