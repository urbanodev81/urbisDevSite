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
    @media (prefers-reduced-motion: reduce) { a.demo-item:hover { transform: none; box-shadow: 0 0 0 1px var(--accent); } }

    /* ---- miniatura viva (10/11/2026) — desenho em `_miniatura.blade.php` ----
       `.a` desloca (troca por pulso de luz com menos movimento); `.c` é só luz
       ou cor (igual nos dois). O desenho parado é o estado completo. */
    .mini { margin: -24px -24px 6px; height: 132px; display: grid; place-items: center; padding: 6px 12px; background: color-mix(in srgb, var(--fg) 4%, var(--surface)); border-bottom: 1px solid var(--border); border-radius: var(--r-card) var(--r-card) 0 0; overflow: hidden; transition: background-color .25s ease-out; }
    a.demo-item:hover .mini, a.demo-item:focus-visible .mini { background: color-mix(in srgb, var(--accent) 8%, var(--surface)); }
    .mini-svg { width: 100%; height: 100%; max-width: 360px; overflow: visible; }
    .mini-svg .a, .mini-svg .c { transform-box: fill-box; animation-duration: 6s; animation-iteration-count: infinite; animation-timing-function: cubic-bezier(.45, 0, .25, 1); animation-delay: var(--d, 0s); }
    .mini-txt { font-family: var(--font-body); font-size: 11px; fill: var(--fg-muted); }
    .mini-txt.forte { fill: var(--fg); font-weight: 600; }
    .mini-txt.grande { font-size: 15px; }
    .mini-linha { fill: var(--surface); stroke: var(--border); }
    .mini-base, .mini-regua { stroke: var(--border-input); stroke-width: 1; }
    .mini-meta { stroke: var(--fg-subtle); stroke-width: 1.5; stroke-dasharray: 5 5; animation-name: mini-pisca; }
    .mini-trilho { fill: var(--border); }
    .mini-trilho-anel { fill: none; stroke: var(--border); stroke-width: 8; }
    .mini-janela, .mini-predio { fill: var(--surface); stroke: var(--border-input); }
    .mini-predio.baixo { fill: color-mix(in srgb, var(--surface) 70%, var(--border)); }
    .mini-pingo { fill: var(--border-input); }
    .mini-logo { fill: color-mix(in srgb, var(--surface) 75%, transparent); }
    .mini-texto { fill: var(--border-input); }
    .mini-titulo { fill: var(--fg-subtle); }
    .mini-livre { fill: var(--surface); stroke: var(--border-input); }
    .mini-ocupado { fill: var(--border); }
    .mini-escolhido { fill: var(--accent); }
    .mini-ok { fill: none; stroke: var(--fg-on-accent); stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .mini-celula { fill: var(--surface); stroke: var(--border-input); animation-name: mini-varre; }
    .mini-seta { fill: none; stroke: var(--accent); stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .mini-balao { fill: var(--surface); stroke: var(--border-input); }
    .mini-balao.eu { fill: color-mix(in srgb, var(--accent) 18%, var(--surface)); stroke: none; }
    .mini-ponto { fill: var(--fg-subtle); }

    /* os movimentos */
    .mini-cresce { transform-origin: 50% 100%; animation-name: mini-cresce; }
    .mini-abre { transform-origin: 50% 50%; animation-name: mini-abre; }
    .mini-enche { transform-origin: 0 50%; animation-name: mini-abre; }
    .mini-escreve { fill: var(--border-input); transform-origin: 0 50%; animation-name: mini-abre; }
    .mini-desce { animation-name: mini-desce; }
    .mini-sobe { animation-name: mini-sobe; }
    .mini-destaque { fill: color-mix(in srgb, var(--accent) 16%, var(--surface)); opacity: 0; animation-name: mini-destaque; }
    .mini-antes { animation-name: mini-antes; }
    .mini-depois { opacity: 0; animation-name: mini-depois; }
    .mini-cai { fill: var(--accent); animation-name: mini-cai; animation-timing-function: ease-in; }
    .mini-anel { fill: none; stroke: var(--serie-1); stroke-width: 8; stroke-linecap: round; stroke-dasharray: 100; stroke-dashoffset: 8; animation-name: mini-anel; }
    .mini-veste { fill: var(--serie-1); animation-name: mini-veste; animation-timing-function: steps(1, end); animation-duration: 7.5s !important; }
    .mini-marca { animation-name: mini-marca; }
    .mini-janelinha { fill: var(--border); animation-name: mini-luz; animation-duration: 5s !important; }
    .mini-cursor { fill: var(--accent); stroke: var(--surface); stroke-width: 3; animation-name: mini-cursor; }
    .mini-custo { fill: var(--accent); transform-origin: 0 50%; animation-name: mini-custo; }
    .mini-digitando { animation-name: mini-digitando; }
    .mini-resposta { animation-name: mini-resposta; }
    .mini-ponto { animation-name: mini-quica; animation-duration: 1s !important; }

    @keyframes mini-cresce { 0%, 8% { transform: scaleY(.08); } 35%, 85% { transform: scaleY(1); } 100% { transform: scaleY(.08); } }
    @keyframes mini-abre { 0%, 8% { transform: scaleX(.05); } 35%, 85% { transform: scaleX(1); } 100% { transform: scaleX(.05); } }
    @keyframes mini-pisca { 0%, 30% { opacity: .25; } 45%, 85% { opacity: 1; } 100% { opacity: .25; } }
    @keyframes mini-desce { 0%, 40% { transform: translateY(0); } 55%, 90% { transform: translateY(25px); } 100% { transform: translateY(0); } }
    @keyframes mini-sobe { 0%, 40% { transform: translateY(0); } 55%, 90% { transform: translateY(-25px); } 100% { transform: translateY(0); } }
    @keyframes mini-destaque { 0%, 30%, 100% { opacity: 0; } 40%, 88% { opacity: 1; } }
    @keyframes mini-antes { 0%, 38%, 96%, 100% { opacity: 1; } 42%, 92% { opacity: 0; } }
    @keyframes mini-depois { 0%, 38%, 96%, 100% { opacity: 0; } 42%, 92% { opacity: 1; } }
    @keyframes mini-cai { 0% { transform: translateY(0); opacity: 0; } 10% { opacity: 1; } 70% { opacity: 1; } 100% { transform: translateY(100px); opacity: 0; } }
    @keyframes mini-anel { 0%, 8% { stroke-dashoffset: 100; } 45%, 85% { stroke-dashoffset: 8; } 100% { stroke-dashoffset: 100; } }
    @keyframes mini-veste { 0% { fill: var(--serie-1); } 33.3% { fill: var(--accent); } 66.6% { fill: var(--serie-3); } }
    @keyframes mini-marca { 0%, 30%, 100% { opacity: 0; } 8%, 25% { opacity: 1; } }
    @keyframes mini-luz { 0%, 100% { fill: var(--border); } 40%, 70% { fill: color-mix(in srgb, var(--accent) 70%, var(--surface)); } }
    @keyframes mini-varre { 0%, 100% { fill: var(--surface); } 12% { fill: color-mix(in srgb, var(--serie-1) 30%, var(--surface)); } 25% { fill: var(--surface); } }
    @keyframes mini-cursor { 0%, 10% { transform: translateX(0); } 50%, 85% { transform: translateX(170px); } 100% { transform: translateX(0); } }
    @keyframes mini-custo { 0%, 10% { transform: scaleX(.15); } 50%, 85% { transform: scaleX(.85); } 100% { transform: scaleX(.15); } }
    @keyframes mini-digitando { 0%, 15% { opacity: 0; } 20%, 40% { opacity: 1; } 45%, 100% { opacity: 0; } }
    @keyframes mini-resposta { 0%, 42% { opacity: 0; } 50%, 92% { opacity: 1; } 100% { opacity: 0; } }
    @keyframes mini-quica { 0%, 60%, 100% { transform: translateY(0); } 30% { transform: translateY(-5px); } }
    @keyframes mini-pulso { 0%, 100% { opacity: .55; } 50% { opacity: 1; } }

    /* Menos movimento: o que desloca vira pulso de luz, no mesmo ritmo. */
    @media (prefers-reduced-motion: reduce) {
        .mini-svg .a { animation-name: mini-pulso !important; transform: none !important; }
    }
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
                            @php($mini = trim(view('demonstracoes._miniatura', ['slug' => $slug])->render()))
                            @if ($mini !== '')
                                <div class="mini">{!! $mini !!}</div>
                            @endif
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
