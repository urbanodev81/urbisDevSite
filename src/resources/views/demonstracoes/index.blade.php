{{--
    A lista das demonstrações. O que entra aqui é o que está em
    `config/demonstracoes.php`, na ordem de lá.
--}}
@extends('layouts.site')

@section('titulo', 'Demonstrações — UrbisDev')
@section('descricao', 'Painéis e páginas de exemplo para você mexer: clique, filtre e veja como ficaria com os dados do seu negócio.')

@push('estilos')
@include('partials.demo-estilos')
<style>
    .demo-lista { display: grid; gap: 20px; padding-block: 32px clamp(56px, 8vw, 96px); }
    @media (min-width: 720px) { .demo-lista { grid-template-columns: repeat(2, 1fr); } }
    .demo-item { display: flex; flex-direction: column; gap: 10px; padding: 24px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-card); text-decoration: none; color: var(--fg-muted); transition: border-color .18s ease-out, transform .18s ease-out; }
    a.demo-item:hover { border-color: var(--accent); transform: translateY(-2px); }
    .demo-item .tipo { font-family: var(--font-mono); font-size: .72rem; letter-spacing: .14em; text-transform: uppercase; color: var(--fg-subtle); }
    .demo-item p { font-size: .98rem; line-height: 1.6; }
    .demo-item .abrir { margin-top: auto; padding-top: 6px; font-weight: 600; color: var(--accent); }
    .demo-item[aria-disabled="true"] .abrir { color: var(--fg-subtle); font-weight: 500; }
    @media (prefers-reduced-motion: reduce) { .demo-item { transition: none; } a.demo-item:hover { transform: none; } }
</style>
@endpush

@section('conteudo')
    <section class="demo-topo" aria-labelledby="demo-titulo">
        <div class="container">
            <span class="eyebrow">Demonstrações</span>
            <h1 id="demo-titulo">Veja funcionando antes de pedir.</h1>
            <p>Cada exemplo abaixo é uma página de verdade, com dados inventados. Clique, filtre, troque o período. Se algum servir para o seu negócio, montamos com os seus dados.</p>
        </div>
    </section>

    <div class="container">
        <ul class="demo-lista" style="list-style:none">
            @foreach ($demos as $slug => $demo)
                @continue(! $demo['pronta'] && ($demo['oculta_se_nao_pronta'] ?? false))
                <li>
                    @if ($demo['pronta'])
                        <a class="demo-item" href="{{ url('/demonstracoes/'.$slug) }}">
                            <span class="tipo">{{ $demo['tipo'] }}</span>
                            <h2 style="font-size:1.35rem">{{ $demo['titulo'] }}</h2>
                            <p>{{ $demo['resumo'] }}</p>
                            <span class="abrir">Abrir a demonstração &rarr;</span>
                        </a>
                    @else
                        <div class="demo-item" aria-disabled="true">
                            <span class="tipo">{{ $demo['tipo'] }}</span>
                            <h2 style="font-size:1.35rem">{{ $demo['titulo'] }}</h2>
                            <p>{{ $demo['resumo'] }}</p>
                            <span class="abrir">Em construção</span>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endsection
