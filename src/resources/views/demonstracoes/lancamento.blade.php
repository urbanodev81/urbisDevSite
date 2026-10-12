{{--
    Ficha de lançamento: os cinco desenhos de página que a base dos sites de
    imobiliária tem. Cada um é uma CÓPIA ESTÁTICA em `public/demos/lancamento/`
    (tirada da base com dado inventado; não há sistema rodando atrás). Para
    refazer a cópia, ver `resources/demos-fonte/lancamento/LEIA.md`.
--}}
@extends('layouts.site')

@section('titulo', 'Ficha de lançamento — demonstração UrbisDev')
@section('descricao', 'Cinco desenhos de página para um lançamento imobiliário: o mesmo empreendimento contado de cinco jeitos. Empreendimento e números inventados.')

@push('estilos')
@include('partials.demo-estilos')
<style>
    .lan-lista { display: grid; gap: 20px; list-style: none; }
    @media (min-width: 720px) { .lan-lista { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1100px) { .lan-lista { grid-template-columns: repeat(3, 1fr); } }
    .lan-item { display: flex; flex-direction: column; height: 100%; background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-card); overflow: hidden; text-decoration: none; color: var(--fg-muted); transition: border-color .18s ease-out, transform .18s ease-out; }
    .lan-item:hover { border-color: var(--accent); transform: translateY(-2px); }
    .lan-item img { display: block; width: 100%; height: auto; aspect-ratio: 8 / 5; object-fit: cover; border-bottom: 1px solid var(--border); background: var(--bg); }
    .lan-item div { display: flex; flex-direction: column; gap: 8px; padding: 18px 20px 20px; flex: 1; }
    .lan-item h2 { font-size: 1.25rem; }
    .lan-item p { font-size: .95rem; line-height: 1.6; }
    .lan-item .abrir { margin-top: auto; padding-top: 6px; font-weight: 600; color: var(--accent); }
    @media (prefers-reduced-motion: reduce) { .lan-item { transition: none; } .lan-item:hover { transform: none; } }
</style>
@endpush

@section('conteudo')
    @include('partials.demo-topo', ['introducao' => 'O mesmo empreendimento contado de cinco jeitos. A imobiliária escolhe o desenho no cadastro do lançamento, e a página se monta com as fotos, plantas e números que ela já lançou no sistema. Abra cada um e role até o fim.'])

    <div class="container demo-corpo">
        <ul class="lan-lista">
            @foreach ([
                'modern' => ['Essencial', 'Galeria que passa sozinha no topo e o essencial logo abaixo. Para quem tem poucas imagens e quer a página no ar hoje.'],
                'classic' => ['Capa cheia', 'Uma imagem ocupa a tela inteira, com nome e faixa de preço por cima. O resto vem em sequência, como uma página de venda.'],
                'minimal' => ['Na planta', 'Texto e números à frente, plantas e implantação em destaque. Para quando ainda não há foto de obra pronta.'],
                'gallery' => ['Galerias', 'As imagens separadas por assunto (fachada, lazer, decorado, plantas), com o resumo e o contato sempre ao lado.'],
                'brochure' => ['Livro', 'Capítulos numerados, do conceito ao andamento da obra. É o material de vendas inteiro, em uma página só.'],
            ] as $arquivo => [$nome, $texto])
                <li>
                    <a class="lan-item" href="/demos/lancamento/{{ $arquivo }}.html">
                        <img src="/demos/lancamento/mini/{{ $arquivo }}.webp" width="720" height="450" loading="lazy" alt="">
                        <div>
                            <h2>{{ $nome }}</h2>
                            <p>{{ $texto }}</p>
                            <span class="abrir">Abrir a ficha &rarr;</span>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>

        @include('partials.demo-chamada', ['chamada' => 'A ficha de lançamento faz parte do site de imobiliária: o cadastro é feito uma vez no sistema e a página, a lista e o formulário de contato saem prontos.'])
    </div>
@endsection
