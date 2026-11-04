<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use UrbanoDev\Blog\Blog;
use UrbanoDev\Blog\Post;

/**
 * Prévia de texto agendado (04/11/2026, pedido do Ricardo ao levar o painel à
 * produção antes do bloco 3). O site público só mostra o que já chegou na
 * data; aqui quem tem `blog.gerenciar` lê o texto futuro com a cara do site.
 *
 * Só agendado: publicado já tem endereço público, rascunho não está pronto.
 * Sem compartilhar, sem "leia também" e sem chamada de contato — o endereço
 * público ainda não existe. `noindex` no cabeçalho e na página, e nada de
 * cache: a prévia é do painel, não do site.
 */
class BlogPreviaController extends Controller
{
    public function __invoke(string $slug, Blog $blog): Response
    {
        $post = collect($blog->agendados())->first(fn (Post $p) => $p->slug === $slug);
        abort_if($post === null, 404);

        return response()
            ->view('painel.blog-previa', [
                'post' => $post,
                'acao' => null,
                'redes' => [],
                'textoParaRedes' => '',
                'relacionados' => [],
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'private, no-store');
    }
}
