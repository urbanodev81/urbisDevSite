<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\BlogComentario;
use App\Models\Contato;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use UrbanoDev\Blog\Blog;
use UrbanoDev\Blog\Post;

/**
 * Início do painel (03/11/2026, bloco 1 da pendência 246).
 *
 * Nasce pequeno de propósito: o que já existe para mostrar é o blog (lido dos
 * `.md`, como o site lê) e o que mudou no painel. Desde o bloco 2
 * (04/11/2026), os contatos que esperam resposta.
 */
class PainelController extends Controller
{
    public function index(Request $request, Blog $blog): Response
    {
        $usuario = $request->user();

        $resumo = fn (Post $p) => [
            'slug' => $p->slug,
            'titulo' => $p->titulo,
            'data' => $p->data->format('d/m/Y'),
            'url' => url('/blog/'.$p->slug),
        ];
        $podePrevia = (bool) $usuario?->can('blog.gerenciar');

        return Inertia::render('Painel/Inicio', [
            // Mesmo raciocínio da trilha: quem não pode ver contatos nem recebe a prop.
            'contatos' => $usuario?->can('leads.ver')
                ? [
                    'novos' => Contato::query()->novos()->count(),
                    'semAviso' => Contato::query()->whereNull('email_enviado_em')->whereNotNull('email_erro')->count(),
                    'esperando' => Contato::query()->novos()->orderBy('created_at')->limit(5)->get()
                        ->map(fn (Contato $c) => [
                            'uuid' => $c->uuid,
                            'nome' => $c->nome,
                            'origem' => $c->origemLegivel(),
                            'dias' => $c->diasEsperando(),
                        ])->all(),
                ]
                : null,

            'blog' => [
                'publicados' => count($blog->publicados()),
                // Todos os agendados (são poucos, ~1 por semana) e, para quem
                // cuida do blog, o link da prévia de cada um.
                'agendados' => array_map(fn (Post $p) => $resumo($p) + [
                    'previa' => $podePrevia ? route('painel.blog.previa', $p->slug) : null,
                ], $blog->agendados()),
                'ultimo' => ($p = $blog->publicados()[0] ?? null) ? $resumo($p) : null,
                // Bloco 3b: comentário só aparece no site depois de aprovado.
                'comentariosPendentes' => $podePrevia ? BlogComentario::query()->pendentes()->count() : null,
            ],

            // Prop que não existe não vaza: quem não pode ver a trilha nem
            // recebe as linhas no JSON da página.
            'atividade' => $usuario?->can('auditoria.ver')
                ? Auditoria::query()->with('autor')->orderByDesc('ocorreu_em')->limit(5)->get()
                    ->map(fn (Auditoria $a) => [
                        'quando' => $a->ocorreu_em->format('d/m H:i'),
                        'quem' => $a->nomeDoAutor(),
                        'evento' => $a->evento,
                        'campo' => $a->campo,
                        'tipo' => $a->tipoLegivel(),
                    ])->all()
                : null,
        ]);
    }
}
