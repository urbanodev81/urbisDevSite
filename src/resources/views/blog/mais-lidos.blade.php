{{--
    "Mais lidos" na página de entrada do blog (bloco 3b da 246, 04/11/2026).
    Encaixe `fim_do_indice`: só aparece em /blog sem filtro, página 1, e só
    com leitura contada (contagem nossa, ver App\Blog\Leitores).
--}}
@if ($inicio)
    @php($maisLidos = app(\App\Blog\Leitores::class)->maisLidos(30, 5))
    @if (count($maisLidos) >= 2)
        @push('estilos')
            <style>
                .blog-mais-lidos { margin-top: 2.5rem; border: 1px solid var(--border); border-radius: var(--r-card); padding: 1.25rem 1.5rem; background: var(--surface); }
                .blog-mais-lidos h2 { font-family: var(--font-display); font-size: 1.2rem; margin: 0 0 .75rem; color: var(--text-hi); }
                .blog-mais-lidos ol { margin: 0; padding-left: 1.5rem; display: grid; gap: .5rem; }
                .blog-mais-lidos li::marker { font-family: var(--font-mono); color: var(--accent); }
                .blog-mais-lidos a { text-decoration: none; }
                .blog-mais-lidos a:hover { text-decoration: underline; }
            </style>
        @endpush
        <nav class="blog-mais-lidos" aria-labelledby="mais-lidos-titulo">
            <h2 id="mais-lidos-titulo">Mais lidos do mês</h2>
            <ol>
                @foreach ($maisLidos as $item)
                    <li><a href="{{ route(config('blog.nome_rota').'.show', $item['post']->slug) }}">{{ $item['post']->titulo }}</a></li>
                @endforeach
            </ol>
        </nav>
    @endif
@endif
