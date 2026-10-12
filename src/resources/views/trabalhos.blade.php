{{--
    Os trabalhos da casa, por categoria. A lista vem do Sócrates
    (App\Support\Portfolio): é lá que se marca o que aparece, em que
    prateleira e se o nome do cliente pode sair.
--}}
@extends('layouts.site')

@section('titulo', 'Trabalhos — UrbisDev')
@section('descricao', 'Sites e sistemas que a UrbisDev já fez, por tipo: o que está no ar e o que está em construção.')

@push('estilos')
@include('partials.demo-estilos')
@include('partials.trabalho-estilos')
<style>
    .trabalhos-indice { display: flex; flex-wrap: wrap; gap: 10px; list-style: none; padding-block: 28px 0; }
    .trabalhos-indice a { display: inline-flex; align-items: center; min-height: 44px; padding: 0 16px; border: 1px solid var(--border); border-radius: 999px; text-decoration: none; font-size: .92rem; font-weight: 500; color: var(--fg-muted); }
    .trabalhos-indice a:hover { border-color: var(--accent); color: var(--fg); }
    .trabalhos-categoria { padding-block: clamp(36px, 5vw, 56px) 0; scroll-margin-top: 88px; }
    .trabalhos-categoria h2 { font-size: clamp(1.5rem, 3vw, 2rem); margin-bottom: 8px; }
    .trabalhos-categoria > p { max-width: 62ch; margin-bottom: 24px; }
    .trabalhos-fim { padding-block: clamp(40px, 6vw, 64px) clamp(56px, 8vw, 96px); }
</style>
@endpush

@section('conteudo')
    <section class="demo-topo" aria-labelledby="trabalhos-titulo">
        <div class="container">
            <span class="eyebrow">Trabalhos</span>
            <h1 id="trabalhos-titulo">O que já saiu daqui.</h1>
            <p>Sites e sistemas feitos pela casa, separados por tipo. Onde o cliente ainda não autorizou o nome, mostramos só o que o trabalho é.</p>
            @if (count($categorias) > 1)
                <ul class="trabalhos-indice" aria-label="Tipos de trabalho">
                    @foreach ($categorias as $categoria)
                        <li><a href="#{{ $categoria['chave'] }}">{{ $categoria['nome'] }}</a></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    <div class="container">
        @foreach ($categorias as $categoria)
            <section class="trabalhos-categoria" id="{{ $categoria['chave'] }}" aria-labelledby="titulo-{{ $categoria['chave'] }}">
                <h2 id="titulo-{{ $categoria['chave'] }}">{{ $categoria['nome'] }}</h2>
                @if ($categoria['descricao'])
                    <p>{{ $categoria['descricao'] }}</p>
                @endif
                <div class="trabalhos">
                    @foreach ($categoria['itens'] as $item)
                        @include('partials.trabalho-cartao', ['item' => $item, 'categoria' => $categoria, 'nivel' => 'h3'])
                    @endforeach
                </div>
            </section>
        @endforeach

        <p class="trabalhos-fim"><a class="btn btn-primary" href="{{ url('/#contato') }}">Quero um assim</a></p>
    </div>
@endsection
