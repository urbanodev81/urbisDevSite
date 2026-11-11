{{--
    Estilos comuns das demonstrações: o cabeçalho da página, os filtros, os
    cartões de número, os cartões de gráfico e as peças que `public/demos/
    graficos.js` desenha (classes `ug-*`).

    Incluído por cada página de demonstração no `@push('estilos')`. Cor só por
    token, como no resto do site.
--}}
<style>
    .demo-topo { padding-block: clamp(40px, 6vw, 72px) 0; }
    .demo-topo .volta { display: inline-flex; align-items: center; min-height: 44px; font-size: .95rem; font-weight: 500; color: var(--fg-muted); text-decoration: none; }
    .demo-topo .volta:hover { color: var(--fg); }
    .demo-topo h1 { font-size: clamp(2rem, 4.4vw, 3.1rem); margin: 10px 0 12px; }
    .demo-topo p { max-width: 62ch; }
    .demo-selo { display: inline-flex; align-items: center; gap: 8px; margin-top: 18px; padding: 6px 12px; border: 1px dashed var(--border-input); border-radius: 999px; font-family: var(--font-mono); font-size: .72rem; letter-spacing: .1em; text-transform: uppercase; color: var(--fg-muted); }

    .demo-porta { max-width: 640px; }
    .demo-porta > p { margin-bottom: 18px; color: var(--fg-muted); line-height: 1.6; }
    .demo-corpo { padding-block: 28px clamp(56px, 8vw, 96px); }
    .demo-filtros { display: flex; flex-wrap: wrap; align-items: end; gap: 12px 20px; padding: 14px 16px; margin-bottom: 20px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-card); }
    .demo-filtros .campo { display: grid; gap: 4px; }
    .demo-filtros label, .demo-filtros .rotulo { font-size: .78rem; font-weight: 600; color: var(--fg-subtle); }
    .demo-filtros select { min-height: 44px; padding: 0 12px; background: var(--bg); color: var(--fg); border: 1px solid var(--border-input); border-radius: var(--r-btn); font: inherit; font-size: .95rem; }
    .demo-filtros input[type=range] { width: min(320px, 70vw); min-height: 44px; accent-color: var(--accent); }
    .demo-botoes { display: inline-flex; border: 1px solid var(--border-input); border-radius: var(--r-btn); overflow: hidden; }
    .demo-botoes button { min-height: 44px; padding: 0 14px; background: var(--bg); color: var(--fg-muted); border: 0; border-right: 1px solid var(--border); font: inherit; font-size: .92rem; cursor: pointer; }
    .demo-botoes button:last-child { border-right: 0; }
    .demo-botoes button[aria-pressed="true"] { background: var(--accent); color: var(--fg-on-accent); font-weight: 600; }

    .demo-numeros { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 20px; }
    @media (min-width: 880px) { .demo-numeros { grid-template-columns: repeat(4, 1fr); } }
    .demo-numero { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-card); padding: 16px 18px; min-width: 0; }
    .demo-numero dt { font-size: .8rem; font-weight: 600; color: var(--fg-subtle); }
    .demo-numero dd { margin-top: 4px; font-family: var(--font-display); font-weight: 700; font-size: clamp(1.25rem, 2.6vw, 1.75rem); line-height: 1.15; color: var(--fg); font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
    .demo-numero small { display: block; margin-top: 4px; font-size: .82rem; line-height: 1.4; color: var(--fg-muted); }

    .demo-grade { display: grid; gap: 20px; }
    @media (min-width: 980px) { .demo-grade { grid-template-columns: repeat(2, minmax(0, 1fr)); } .demo-grade .larga { grid-column: 1 / -1; } }
    .demo-cartao { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-card); padding: 20px; min-width: 0; }
    .demo-cartao h2 { font-family: var(--font-body); font-size: 1.05rem; font-weight: 600; letter-spacing: 0; line-height: 1.3; }
    .demo-cartao .sub { margin: 2px 0 14px; font-size: .88rem; line-height: 1.5; color: var(--fg-muted); }

    .demo-chamada { margin-top: 28px; padding: 24px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px 24px; background: var(--surface-raised); border: 1px solid var(--border); border-radius: var(--r-card); }
    .demo-chamada strong { display: block; font-family: var(--font-display); font-size: 1.25rem; color: var(--fg); }
    .demo-chamada p { font-size: .95rem; max-width: 56ch; }

    /* ---- peças desenhadas pelo graficos.js ---- */
    .ug svg { display: block; max-width: 100%; height: auto; overflow: visible; }
    .ug text { font-family: var(--font-body); font-size: 12px; }
    .ug-eixo { fill: var(--fg-subtle); }
    .ug-rotulo { fill: var(--fg-muted); font-size: 13px !important; }
    .ug-valor { fill: var(--fg); font-weight: 600; font-variant-numeric: tabular-nums; }
    .ug-grade { stroke: var(--border); stroke-width: 1; }
    .ug-linha { fill: none; stroke-width: 2; stroke-linejoin: round; stroke-linecap: round; }
    .ug-linha-meta { stroke-dasharray: 5 5; stroke-width: 1.5; }
    .ug-ponto { stroke: var(--surface); stroke-width: 2; }
    .ug-mira { stroke: var(--fg-subtle); stroke-width: 1; pointer-events: none; }
    .ug-celula { fill: var(--serie-1); }
    .ug-legenda { display: flex; flex-wrap: wrap; gap: 6px 18px; list-style: none; margin-bottom: 10px; font-size: .85rem; color: var(--fg-muted); }
    .ug-legenda li { display: inline-flex; align-items: center; gap: 8px; }
    .ug-legenda i { width: 14px; height: 3px; border-radius: 2px; flex: none; }
    .ug-legenda i.ug-traco { height: 2px; background-image: linear-gradient(90deg, var(--surface) 0 35%, transparent 35% 100%) !important; background-size: 6px 100%; }
    .ug-chave { display: flex; align-items: center; gap: 8px; margin-top: 8px; font-size: .8rem; color: var(--fg-subtle); }
    .ug-chave i { width: 96px; height: 8px; border-radius: 4px; background: linear-gradient(90deg, color-mix(in srgb, var(--serie-1) 10%, var(--surface)), var(--serie-1)); }
    .ug-tabela { margin-top: 12px; font-size: .85rem; }
    .ug-tabela summary { display: inline-flex; align-items: center; min-height: 44px; cursor: pointer; color: var(--fg-muted); text-decoration: underline; text-underline-offset: 3px; }
    .ug-tabela-rolagem { overflow-x: auto; }
    .ug-tabela table, .demo-tabela { width: 100%; border-collapse: collapse; font-variant-numeric: tabular-nums; }
    .ug-tabela caption { text-align: left; padding-bottom: 6px; color: var(--fg-subtle); }
    .ug-tabela th, .ug-tabela td, .demo-tabela th, .demo-tabela td { padding: 8px 10px; border-bottom: 1px solid var(--border); text-align: right; white-space: nowrap; }
    .ug-tabela th:first-child, .ug-tabela td:first-child, .demo-tabela th:first-child, .demo-tabela td:first-child { text-align: left; }
    .ug-tabela thead th, .demo-tabela thead th { font-size: .76rem; font-weight: 600; color: var(--fg-subtle); }
    .ug-tabela tbody th, .demo-tabela tbody th { font-weight: 500; color: var(--fg); }
    .demo-tabela-rolagem { overflow-x: auto; }

    .ug-dica { position: fixed; z-index: 1000; pointer-events: none; min-width: 150px; max-width: 280px; padding: 10px 12px; background: var(--surface-raised); border: 1px solid var(--border-input); border-radius: 10px; box-shadow: var(--shadow-painel); font-size: .82rem; line-height: 1.45; color: var(--fg-muted); }
    .ug-dica[hidden] { display: none; }
    .ug-dica strong { display: block; margin-bottom: 4px; color: var(--fg); }
    .ug-dica p { display: flex; align-items: center; gap: 8px; }
    .ug-dica i { width: 10px; height: 10px; border-radius: 2px; flex: none; }
    .ug-dica b { margin-left: auto; padding-left: 14px; color: var(--fg); font-variant-numeric: tabular-nums; }
    /* ---- movimento (10/11/2026) ----
       Entrada das peças do graficos.js e das tabelas. Fica parada até a peça
       aparecer na tela (`ug-visto`, posto pelo graficos.js). Com menos
       movimento, crescer/desenhar/subir TROCAM por acender, no mesmo ritmo
       (regra da casa: nunca desligar). "Pausar animações" do widget encurta
       tudo para o estado final. */
    @keyframes ug-cresce-y { from { transform: scaleY(0); } }
    @keyframes ug-cresce-x { from { transform: scaleX(0); } }
    @keyframes ug-desenha { from { stroke-dashoffset: 1; } to { stroke-dashoffset: 0; } }
    @keyframes ug-acende { from { opacity: 0; } }
    @keyframes ug-sobe { from { opacity: 0; transform: translateY(8px); } }
    .ug-cresce-y, .ug-cresce-x { transform-box: fill-box; animation: ug-cresce-y .75s cubic-bezier(.2, .75, .25, 1) both; animation-delay: calc(var(--i, 0) * 70ms); }
    .ug-cresce-y { transform-origin: 50% 100%; }
    .ug-cresce-x { transform-origin: 0 50%; animation-name: ug-cresce-x; }
    .ug-desenha { stroke-dasharray: 1; animation: ug-desenha 1.2s cubic-bezier(.4, 0, .2, 1) both; }
    .ug-acende { animation: ug-acende .6s ease-out both; animation-delay: calc(var(--i, 0) * 45ms); }
    .ug-acende.ug-depois { animation-delay: calc(500ms + var(--i, 0) * 70ms); }
    .ug-desenha ~ .ug-depois { animation-delay: 1.1s; }
    .ug:not(.ug-visto) :is(.ug-cresce-y, .ug-cresce-x, .ug-desenha, .ug-acende) { animation-play-state: paused; }
    .ug-quieto :is(.ug-cresce-y, .ug-cresce-x, .ug-desenha, .ug-acende) { animation: none; }
    .demo-tabela tbody tr { animation: ug-sobe .45s ease-out both; }
    .demo-tabela tbody tr:nth-child(2) { animation-delay: 45ms; }
    .demo-tabela tbody tr:nth-child(3) { animation-delay: 90ms; }
    .demo-tabela tbody tr:nth-child(4) { animation-delay: 135ms; }
    .demo-tabela tbody tr:nth-child(5) { animation-delay: 180ms; }
    .demo-tabela tbody tr:nth-child(6) { animation-delay: 225ms; }
    .demo-tabela tbody tr:nth-child(7) { animation-delay: 270ms; }
    .demo-tabela tbody tr:nth-child(8) { animation-delay: 315ms; }
    .demo-tabela tbody tr:nth-child(9) { animation-delay: 360ms; }
    .demo-tabela tbody tr:nth-child(10) { animation-delay: 405ms; }
    .demo-tabela tbody tr:nth-child(11) { animation-delay: 450ms; }
    .demo-tabela tbody tr:nth-child(12) { animation-delay: 495ms; }
    .demo-tabela:not(.ug-visto) tbody tr { animation-play-state: paused; }
    .ug-acendeu { animation: ug-acende .5s ease-out; }
    .demo-numero { transition: border-color .3s ease-out, box-shadow .3s ease-out; }
    .demo-numero:hover { border-color: var(--accent); box-shadow: 0 6px 18px -10px color-mix(in srgb, var(--accent) 60%, transparent); }
    @media (prefers-reduced-motion: reduce) {
        .ug-cresce-y, .ug-cresce-x { transform: none; animation-name: ug-acende; }
        .ug-desenha { stroke-dasharray: none; animation-name: ug-acende; }
        .demo-tabela tbody tr { animation-name: ug-acende; }
    }
</style>
