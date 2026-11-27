{{--
    Home do site — a "cidade à noite".

    O casco (head, header, rodapé, acessibilidade, flutuantes, rolagem suave)
    saiu daqui em 23/08/2026 para `layouts/site.blade.php`. Ficou o que é
    DESTA página: o palco do topo (desde 25/11/2026; até ali, a grade de
    janelas acesas), os distritos (as frentes) e o
    formulário de contato. "Próximos passos" saiu em 18/10/2026: anunciava como
    planejado o white-label que já estava no ar.

    A extração pagou na hora: as correções de alvo de toque de 44px tinham
    sido feitas só na página "Como trabalhamos", e a home tinha ficado com
    `.nav-toggle` de 8px de padding, link de menu sem altura mínima e ícone de
    rede social de 38px. Não era decisão, era cópia envelhecendo sozinha —
    agora o casco é um só e a correção vale nas duas.
--}}
@extends('layouts.site')

@section('titulo', 'UrbisDev — Software que resolve problemas reais')
@section('descricao', 'Sites com painel próprio e sistemas de gestão para imobiliárias, clínicas, sindicatos, oficinas e artistas — do primeiro esboço ao app no celular.')

@push('head')
    {{-- Altcha auto-hospedado (29/09/2026; era o Turnstile da Cloudflare).
         A tradução vem ANTES do widget: ela registra o pt-br no objeto global
         que o widget lê ao montar. Os dois são módulos, que já executam em
         ordem e depois do HTML — sem `defer` a acrescentar. --}}
    <script type="module" src="/vendor/altcha/pt-br-3.2.2.js"></script>
    <script type="module" src="/vendor/altcha/altcha-3.2.2.min.js"></script>
@endpush

@push('estilos')
@include('partials.trabalho-estilos')
<style>
        .hero-ctas { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 36px; }

        /* ---------- palco do topo (25/11/2026, `public/topo.js`) ----------
           Veio da vitrine do urbanodev.com.br (`sistemas/urbanodev-com-br/web`,
           commit 264d073): o logo grande no centro, o título do painel como lema,
           ações reais dos nossos projetos vagando em volta e layouts nossos
           passando ao fundo. ESCURO NOS DOIS TEMAS (`.sempre-escuro`, ver
           `partials/tokens.blade.php`); o resto da página segue o tema.
           Ordem de empilhamento: layouts (-2), palavras (-1), centro. */
        .palco { position: relative; min-height: clamp(540px, 80vh, 860px); min-height: clamp(540px, 80svh, 860px); display: grid; place-items: center; overflow: hidden; isolation: isolate;
            background-color: var(--bg); color: var(--fg-muted);
            background-image:
                radial-gradient(circle 420px at var(--gx, 50%) var(--gy, 50%), color-mix(in srgb, var(--accent) 7%, transparent), transparent 70%),
                radial-gradient(ellipse 50% 38% at 50% 50%, color-mix(in srgb, var(--accent) 16%, transparent), transparent 70%); }
        .nuvem { position: absolute; inset: 0; z-index: -1; pointer-events: none; }
        .palavra { position: absolute; white-space: nowrap; pointer-events: none; will-change: transform;
            font: 500 calc(.78rem + var(--prof) * .55rem) var(--font-mono);
            color: var(--fg-subtle); opacity: calc(.28 + var(--prof) * .62);
            animation: surgir 1.1s ease-out var(--surge) backwards; }
        .palavra.quente { color: var(--accent); }
        /* "acender": a ação acontecendo agora. `scale` é propriedade própria, não briga com o transform do JS */
        .palavra.viva { animation: acender 1.6s ease-out; }
        @keyframes surgir { from { opacity: 0; filter: blur(8px); } }
        @keyframes acender {
            0%   { scale: 1; }
            18%  { scale: 1.45; color: var(--accent); opacity: 1; text-shadow: 0 0 18px color-mix(in srgb, var(--accent) 16%, transparent), 0 0 2px var(--accent); }
            100% { scale: 1; }
        }
        @keyframes acender-calmo { 25% { color: var(--accent); opacity: 1; text-shadow: 0 0 18px color-mix(in srgb, var(--accent) 16%, transparent), 0 0 2px var(--accent); } }
        @keyframes letra-calma { from { opacity: 0; filter: blur(10px); } }

        /* Layouts nossos ao fundo, montados pelo topo.js depois do `load`. Faixas
           em cima e embaixo; somem no centro, onde mora o logo. O `#000` da
           máscara NÃO é cor: `mask-image` lê só o alfa, e preto é "opaco". */
        .layouts { position: absolute; inset: -6% -8%; z-index: -2; display: flex; flex-direction: column; justify-content: space-between; padding: 4vh 0;
            pointer-events: none; transform: rotate(-6deg); opacity: 0; transition: opacity 1.6s ease-out;
            -webkit-mask-image: radial-gradient(ellipse 46% 34% at 50% 50%, transparent 55%, #000 100%);
            mask-image: radial-gradient(ellipse 46% 34% at 50% 50%, transparent 55%, #000 100%); }
        .layouts.pronto { opacity: .28; }
        .layouts-trilho { display: flex; gap: 22px; width: max-content; animation: passar 50s linear infinite; }
        .layouts-trilho.volta { animation-duration: 64s; animation-direction: reverse; margin-left: -180px; }
        .layouts-trilho img { width: 320px; height: 180px; border-radius: 10px; border: 1px solid var(--border); object-fit: cover; object-position: top; filter: saturate(.55); transition: opacity .9s ease; }
        .layouts-trilho img.trocando { opacity: 0; }
        @keyframes passar { to { transform: translateX(-50%); } }
        .palco.fora .layouts-trilho { animation-play-state: paused; }
        /* modo calmo: as fileiras ficam paradas e centradas; as imagens trocam esmaecendo */
        .layouts.calmo .layouts-trilho { animation: none; margin-left: 0; align-self: center; }
        @media (max-width: 700px) { .layouts-trilho img { width: 200px; height: 113px; } }

        /* o logo: o selo se desenha, as letras entram uma a uma (spans no Blade) */
        .palco-centro { text-align: center; padding: 0 20px; }
        .palco-logo { display: inline-flex; align-items: center; gap: clamp(14px, 2.4vw, 28px); }
        .palco-selo { width: clamp(56px, 9vw, 108px); height: auto; aspect-ratio: 1; overflow: visible; }
        .palco-selo rect { fill: none; stroke: var(--accent); stroke-width: 3.5; stroke-dasharray: 1; animation: desenhar 1.3s cubic-bezier(.65, 0, .35, 1) .1s backwards; }
        .palco-selo text { fill: var(--accent); font: 500 30px var(--font-mono); animation: surgir .8s ease-out 1s backwards; }
        @keyframes desenhar { from { stroke-dashoffset: 1; } to { stroke-dashoffset: 0; } }
        /* o selo brilha quando uma palavra acende (brilho, não deslocamento) */
        .palco-selo.pulsa { animation: brilho-selo 1.3s ease-out; }
        @keyframes brilho-selo { 25% { filter: drop-shadow(0 0 14px var(--accent)) drop-shadow(0 0 3px var(--accent)); } }
        .palco-nome { font: 800 clamp(3.2rem, 11vw, 8.6rem)/1 var(--font-display); letter-spacing: -.04em; color: var(--fg); }
        .palco-nome .dev { color: var(--accent); }
        .palco-nome .letra { display: inline-block; animation: letra 1s cubic-bezier(.2, .8, .2, 1) calc(var(--k) * 55ms + 250ms) backwards; }
        @keyframes letra { from { opacity: 0; transform: translateY(.45em) rotate(8deg); filter: blur(10px); } }
        /* o lema é o título do painel e é o h1 da página (o logo já está no cabeçalho) */
        .palco .lema { margin: 18px auto 0; max-width: 44ch; font: 500 clamp(1.05rem, 2vw, 1.3rem)/1.5 var(--font-body); letter-spacing: 0; color: var(--fg-muted); }
        .palco .lema em { font-style: normal; color: var(--accent); }
        .palco .hero-ctas { justify-content: center; margin-top: 28px; }
        .palco .btn-primary { min-height: 48px; padding-inline: 26px; transition: background .18s ease-out, transform .35s cubic-bezier(.2, .8, .2, 1); }
        .palco .lema, .palco .hero-ctas { animation: subir .9s cubic-bezier(.2, .7, .2, 1) .9s backwards; }
        .palco .hero-ctas { animation-delay: 1.05s; }
        @keyframes subir { from { opacity: 0; transform: translateY(18px); } }
        .descer { position: absolute; bottom: 24px; left: 50%; translate: -50% 0; width: 44px; height: 44px; display: grid; place-items: center; color: var(--fg-subtle); animation: quicar 2.4s ease-in-out infinite; }
        .descer:hover { color: var(--accent); }
        @keyframes quicar { 50% { transform: translateY(6px); } }
        /* em tela baixa (celular deitado) a seta encostaria nos botões */
        @media (max-height: 620px) { .descer { display: none; } }

        /* Modo calmo (25/11/2026): opção "Reduzir movimento" do widget, não o
           sistema. Nada se desloca; cor, brilho, esmaecer e o traço do selo
           crescendo no lugar continuam. Mesma regra da vitrine. */
        html.a11y-calmo .palavra:not(.viva), html.a11y-calmo .descer, html.a11y-calmo .palco .lema, html.a11y-calmo .palco .hero-ctas { animation: none; }
        html.a11y-calmo .palavra { transform: none !important; }
        html.a11y-calmo .palavra.viva { animation: acender-calmo 1.6s ease-out; }
        html.a11y-calmo .palco-nome .letra { animation-name: letra-calma; }
        html.a11y-calmo .palco .btn-primary { transform: none !important; }

        /* ---------- banner de lançamentos ---------- */
        .lancamentos { position: relative; background: var(--ink-800); border-block: 1px solid var(--line); }
        .lancamentos::before { content: ""; position: absolute; top: -1px; left: 0; right: 0; height: 3px; background: var(--d-urb); }
        .lanc-trilho { display: grid; gap: 64px; }
        /* Todos na mesma célula: a altura é a do maior e a página não pula na troca. */
        .em-passagem .lanc-slide { grid-area: 1 / 1; visibility: hidden; opacity: 0; transition: opacity .35s ease-out, visibility 0s linear .35s; }
        .em-passagem .lanc-slide.ativo { visibility: visible; opacity: 1; transition: opacity .35s ease-out; }
        .lanc-slide { display: grid; gap: 40px; align-items: center; }
        @media (min-width: 880px) { .lanc-slide { grid-template-columns: 1fr 1.1fr; gap: 64px; } }
        .lanc-slide h2 { margin: 16px 0 14px; }
        .lanc-slide .hero-ctas { margin-top: 28px; }
        .lanc-nota { margin-top: 16px; font-size: .9rem; color: var(--text-low); }
        .lanc-tela { display: block; border: 1px solid var(--line); border-radius: var(--r-card); overflow: hidden; transition: transform .2s ease-out, border-color .2s ease-out; }
        .lanc-tela:hover { transform: translateY(-3px); border-color: var(--d-urb); }
        .lanc-tela img { display: block; width: 100%; height: auto; }
        .lanc-controles { display: none; align-items: center; gap: 4px; margin-top: 28px; }
        .em-passagem .lanc-controles { display: flex; }
        .lanc-controles button { display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; padding: 0; background: none; border: 0; border-radius: 50%; color: var(--fg); cursor: pointer; }
        .lanc-controles button:hover { color: var(--accent); }
        .lanc-pausa [hidden] { display: none; }
        .lanc-ponto::before { content: ""; width: 10px; height: 10px; border-radius: 50%; border: 2px solid var(--text-mid); }
        .lanc-ponto:hover::before { border-color: var(--accent); }
        .lanc-ponto[aria-current="true"]::before { background: var(--accent); border-color: var(--accent); }
        @media (prefers-reduced-motion: reduce) { .em-passagem .lanc-slide, .em-passagem .lanc-slide.ativo, .lanc-tela { transition: none; } .lanc-tela:hover { transform: none; } }

        /* ---------- demonstrações ---------- */
        .trabalho-de-tipo { display: flex; flex-direction: column; gap: 10px; }
        .trabalho-de-tipo .trabalho { flex: 1; }
        .trabalho-de-tipo .tag { font-family: var(--font-mono); font-size: .72rem; letter-spacing: .14em; text-transform: uppercase; color: var(--fg-subtle); }
        .trabalho-de-tipo .ver-mais { align-self: flex-start; display: inline-flex; align-items: center; min-height: 44px; font-weight: 600; color: var(--accent); text-decoration: none; }
        .trabalho-de-tipo .ver-mais:hover { text-decoration: underline; }
        .demos-home { display: grid; gap: 20px; }
        @media (min-width: 880px) { .demos-home { grid-template-columns: repeat(3, 1fr); } }
        .demo-home { display: flex; flex-direction: column; gap: 10px; padding: 24px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-card); text-decoration: none; color: var(--fg-muted); transition: border-color .18s ease-out, transform .18s ease-out; }
        .demo-home:hover { border-color: var(--accent); transform: translateY(-3px); }
        .demo-home .tag { font-family: var(--font-mono); font-size: .72rem; letter-spacing: .14em; text-transform: uppercase; color: var(--fg-subtle); }
        .demo-home p { font-size: .98rem; line-height: 1.6; }
        .demo-home .abrir { margin-top: auto; padding-top: 6px; font-weight: 600; color: var(--accent); }
        @media (prefers-reduced-motion: reduce) { .demo-home { transition: none; } .demo-home:hover { transform: none; } }

        /* ---------- seções / cards ---------- */
        .section-head { max-width: 640px; margin-bottom: clamp(40px, 6vw, 64px); }
        .section-head h2 { margin: 16px 0 14px; }
        .districts { display: grid; gap: 20px; }
        @media (min-width: 720px) { .districts { grid-template-columns: repeat(2, 1fr); } .district:last-child:nth-child(odd) { grid-column: 1 / -1; } .district:last-child:nth-child(odd) p { max-width: 68ch; } }
        .district { position: relative; background: var(--ink-800); border: 1px solid var(--line); border-radius: var(--r-card); padding: 28px 26px 26px; display: flex; flex-direction: column; gap: 14px; transition: transform .2s ease-out, border-color .2s ease-out; color: var(--text-mid); }
        .district::before { content: ""; position: absolute; top: 0; left: 24px; right: 24px; height: 3px; border-radius: 0 0 3px 3px; background: var(--dc, var(--amber-500)); }
        .district:hover { transform: translateY(-3px); border-color: color-mix(in srgb, var(--dc, var(--amber-500)) 40%, var(--line)); }
        .district .tag { font-family: var(--font-mono); font-size: .72rem; letter-spacing: .14em; text-transform: uppercase; color: var(--text-low); }
        .district .tag b { color: var(--dc, var(--amber-500)); font-weight: 500; }
        .district h3 { font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; }
        .district p { font-size: .98rem; flex: 1; }
        .district .chip { align-self: flex-start; }
        .district .card-icon { color: var(--dc, var(--amber-500)); }
        .district-link { align-self: flex-start; display: inline-flex; align-items: center; min-height: 44px; font-weight: 600; color: var(--accent); text-decoration: none; }
        .district-link:hover { color: var(--accent-hover); text-decoration: underline; }
        .chip { display: inline-flex; align-items: center; gap: 7px; font-family: var(--font-mono); font-size: .7rem; letter-spacing: .1em; text-transform: uppercase; border: 1px solid var(--line); border-radius: 999px; padding: 5px 12px; color: var(--text-mid); }
        .chip::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: var(--ok); }
        .chip--dev::before { background: var(--warn); }

        /* ---------- contato ---------- */
        .contact { border-top: 1px solid var(--line); background: var(--ink-800); }
        .contact-grid { display: grid; gap: 48px; }
        @media (min-width: 880px) { .contact-grid { grid-template-columns: 1fr 1.2fr; gap: 80px; } }
        .contact-info p { margin: 14px 0 28px; max-width: 44ch; }
        /* 44px de altura: são LINKS (mailto: e o site), e estavam com 27px —
           a última sobra da dívida de alvo de toque da home. */
        .contact-line { display: flex; align-items: center; gap: 12px; min-height: 44px; font-size: .98rem; color: var(--text-hi); margin-bottom: 12px; text-decoration: none; }
        .contact-line svg { color: var(--amber-500); flex: none; }
        .contact-line:hover { color: var(--amber-400); }
        @include('partials.formulario-estilos')
        .char-count { font-family: var(--font-mono); font-size: .72rem; color: var(--text-low); text-align: right; }

        /* ---------- modal de feedback ---------- */
        .modal-overlay { position: fixed; inset: 0; z-index: 9999; background: var(--overlay); backdrop-filter: blur(3px); display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity .3s ease-out; }
        .modal-overlay.active { opacity: 1; pointer-events: auto; }
        .modal { background: var(--ink-800); border: 1px solid var(--line); border-radius: var(--r-card); padding: 40px 32px 32px; max-width: 440px; width: 90%; text-align: center; transform: translateY(20px); transition: transform .3s ease-out; }
        .modal-overlay.active .modal { transform: translateY(0); }
        .modal-icon { width: 64px; height: 64px; border-radius: 50%; border: 1px solid var(--line); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px; }
        .modal-icon.success { color: var(--ok); border-color: color-mix(in srgb, var(--ok) 40%, var(--line)); }
        .modal-icon.error { color: var(--error); border-color: color-mix(in srgb, var(--error) 40%, var(--line)); }
        .modal h3 { font-family: var(--font-display); font-size: 1.35rem; font-weight: 700; margin-bottom: 8px; }
        .modal p { color: var(--text-mid); font-size: .98rem; margin-bottom: 24px; }
</style>
@endpush

@section('conteudo')

        @php($lancamentos = \App\Support\LancamentosDoSite::lista())
        {{-- Palco (25/11/2026): logo no centro, título do painel como lema, os dois
             botões. Escuro nos dois temas. Palavras e layouts são do topo.js. --}}
        <section class="palco sempre-escuro" id="palco" aria-labelledby="lema">
            <div class="palco-centro">
                <div class="palco-logo" aria-hidden="true">
                    <svg class="palco-selo" viewBox="0 0 100 100">
                        <rect x="4" y="4" width="92" height="92" rx="22" pathLength="1"/>
                        <text x="50" y="62" text-anchor="middle">UD</text>
                    </svg>
                    <span class="palco-nome"><span class="letra" style="--k:0">u</span><span class="letra" style="--k:1">r</span><span class="letra" style="--k:2">b</span><span class="letra" style="--k:3">i</span><span class="letra" style="--k:4">s</span><span class="dev"><span class="letra" style="--k:5">D</span><span class="letra" style="--k:6">e</span><span class="letra" style="--k:7">v</span></span></span>
                </div>
                {{-- O título vem do painel, Configurações (bloco 4a, 06/11/2026); *asteriscos* viram o destaque. --}}
                <h1 class="lema" id="lema">{!! \App\Support\ConfiguracaoDoSite::comEnfase('home.hero_titulo') !!}</h1>
                <div class="hero-ctas">
                    <a class="btn btn-primary" href="#contato">Fale conosco
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                    <a class="btn btn-ghost" href="#demonstracoes">Veja funcionando</a>
                </div>
            </div>
            <a class="descer" href="{{ $lancamentos ? '#lancamentos' : '#fazemos' }}" aria-label="Rolar para baixo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
            </a>
        </section>

        {{-- Banner de lançamentos, logo depois do hero (30/10/2026): o que a casa já
             lançou e tem endereço público. Os slides vêm de Site › Lançamentos
             (App\Support\LancamentosDoSite, bloco 4c, 06/11/2026); nenhum ativo, o
             banner some. A passagem mora em `public/lancamentos.js`. --}}
        @if ($lancamentos)
        <section id="lancamentos" class="lancamentos" aria-label="Lançamentos" aria-roledescription="carrossel">
            <div class="container">
                <div class="lanc-trilho">
                    @foreach ($lancamentos as $lancamento)
                        <div class="lanc-slide {{ $loop->first ? 'ativo' : '' }}" role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} de {{ $loop->count }}">
                            <div>
                                <span class="eyebrow">{{ $lancamento['etiqueta'] }}</span>
                                <h2>{{ $lancamento['titulo'] }}</h2>
                                <p>{{ $lancamento['texto'] }}</p>
                                <div class="hero-ctas">
                                    <a class="btn btn-primary" href="{{ $lancamento['url'] }}">{{ $lancamento['chamada'] }}
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                    </a>
                                    @if ($lancamento['secundario_url'])
                                        <a class="btn btn-ghost" href="{{ $lancamento['secundario_url'] }}">{{ $lancamento['secundario'] }}</a>
                                    @endif
                                </div>
                                @if ($lancamento['nota'])
                                    <p class="lanc-nota">{{ $lancamento['nota'] }}</p>
                                @endif
                            </div>
                            <a class="lanc-tela" href="{{ $lancamento['url'] }}" tabindex="-1" aria-hidden="true">
                                <img src="{{ $lancamento['imagem_url'] }}" alt="" width="1200" height="750" loading="lazy" decoding="async">
                            </a>
                        </div>
                    @endforeach
                </div>
                @if (count($lancamentos) > 1)
                    <div class="lanc-controles">
                        <button type="button" class="lanc-anterior" aria-label="Lançamento anterior"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></button>
                        @foreach ($lancamentos as $lancamento)
                            <button type="button" class="lanc-ponto" aria-label="Ver lançamento {{ $loop->iteration }} de {{ $loop->count }}"></button>
                        @endforeach
                        <button type="button" class="lanc-proximo" aria-label="Próximo lançamento"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></button>
                        <button type="button" class="lanc-pausa">
                            <svg class="lanc-icone-pausa" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>
                            <svg class="lanc-icone-tocar" hidden width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 4.5v15l12-7.5z"/></svg>
                        </button>
                    </div>
                @endif
            </div>
        </section>
        @endif

        {{-- O que fazemos --}}
        {{-- As frentes vêm de Site › Serviços (App\Support\ServicosDoSite, bloco 4b,
             06/11/2026); banco fora, os 4 de sempre. Sem nome de produto e sem
             dizer para quem é a obra (decisão de 18/10/2026); a exceção é o USI,
             lançado, com endereço público (30/10/2026). Nenhum serviço ativo:
             a seção não existe. --}}
        @php($servicos = \App\Support\ServicosDoSite::lista())
        @if ($servicos)
        <section id="fazemos">
            <div class="container">
                <div class="section-head reveal">
                    <span class="eyebrow">{{ $site('home.fazemos_etiqueta') }}</span>
                    <h2>{{ $site('home.fazemos_titulo') }}</h2>
                </div>
                <div class="districts">
                    @foreach ($servicos as $servico)
                    <div class="district reveal" style="--dc:{{ $servico['cor_css'] }}">
                        <span class="tag">Frente · <b>{{ $servico['frente'] }}</b></span>
                        <svg class="card-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $servico['svg'] !!}</svg>
                        <h3>{{ $servico['titulo'] }}</h3>
                        <p>{{ $servico['texto'] }}</p>
                        @if ($servico['link_url'])
                        <a class="district-link" href="{{ $servico['link_url'] }}">{{ $servico['link_rotulo'] }}&nbsp;&rarr;</a>
                        @endif
                        <span @class(['chip', 'chip--dev' => $servico['situacao'] === 'em_construcao'])>{{ $servico['situacao_rotulo'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        {{-- Tipos de site: um trabalho de cada categoria do portfólio, que vem do
             Sócrates (App\Support\Portfolio). Sem nenhum marcado lá, a seção
             não existe. A lista inteira mora em /trabalhos. --}}
        @if (! empty($trabalhos))
        <section id="trabalhos">
            <div class="container">
                <div class="section-head reveal">
                    <span class="eyebrow">{{ $site('home.trabalhos_etiqueta') }}</span>
                    <h2>{{ $site('home.trabalhos_titulo') }}</h2>
                    @if ($site('home.trabalhos_texto'))<p>{{ $site('home.trabalhos_texto') }}</p>@endif
                </div>
                <div class="trabalhos reveal">
                    @foreach ($trabalhos as $categoria)
                        <div class="trabalho-de-tipo">
                            <p class="tag">{{ $categoria['nome'] }}</p>
                            @include('partials.trabalho-cartao', ['item' => $categoria['itens'][0], 'categoria' => $categoria, 'nivel' => 'h3'])
                            @if (count($categoria['itens']) > 1)
                                <a class="ver-mais" href="{{ url('/trabalhos#'.$categoria['chave']) }}">Ver mais<span class="sr-only"> em {{ $categoria['nome'] }}</span>&nbsp;&rarr;</a>
                            @endif
                        </div>
                    @endforeach
                </div>
                <p class="reveal" style="margin-top:28px"><a class="btn btn-ghost" href="{{ url('/trabalhos') }}">Ver outros sites</a></p>
            </div>
        </section>
        @endif

        {{-- Demonstrações: as três primeiras de `config/demonstracoes.php`.
             A lista inteira mora em /demonstracoes. --}}
        <section id="demonstracoes">
            <div class="container">
                <div class="section-head reveal">
                    <span class="eyebrow">{{ $site('home.demonstracoes_etiqueta') }}</span>
                    <h2>{{ $site('home.demonstracoes_titulo') }}</h2>
                    @if ($site('home.demonstracoes_texto'))<p>{{ $site('home.demonstracoes_texto') }}</p>@endif
                </div>
                <div class="demos-home reveal">
                    @foreach (collect($demos ?? [])->where('pronta', true)->take(3) as $slug => $demo)
                        <a class="demo-home" href="{{ url('/demonstracoes/'.$slug) }}">
                            <span class="tag">{{ $demo['tipo'] }}</span>
                            <h3>{{ $demo['titulo'] }}</h3>
                            <p>{{ $demo['resumo'] }}</p>
                            <span class="abrir">Abrir a demonstração &rarr;</span>
                        </a>
                    @endforeach
                </div>
                <p class="reveal" style="margin-top:28px"><a class="btn btn-ghost" href="{{ url('/demonstracoes') }}">Ver todas as demonstrações</a></p>
            </div>
        </section>

        {{-- Contato --}}
        <section id="contato" class="contact">
            <div class="container contact-grid">
                <div class="contact-info reveal">
                    <span class="eyebrow">{{ $site('home.contato_etiqueta') }}</span>
                    <h2 style="margin-top:16px">{{ $site('home.contato_titulo') }}</h2>
                    @if ($site('home.contato_texto'))<p>{{ $site('home.contato_texto') }}</p>@endif
                    <a class="contact-line" href="mailto:{{ $site('contato.email') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        {{ $site('contato.email') }}
                    </a>
                    <a class="contact-line" href="https://urbisdev.tech">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
                        urbisdev.tech
                    </a>
                </div>

                <form action="/contato" method="POST" class="reveal">
                    @csrf
                    {{-- De onde a pessoa veio, para o painel (04/11/2026). Só slug; o servidor confere. --}}
                    @if (old('de_demo', $deDemo ?? null))<input type="hidden" name="de_demo" value="{{ old('de_demo', $deDemo ?? null) }}">@endif
                    @if (old('de_texto', $deTexto ?? null))<input type="hidden" name="de_texto" value="{{ old('de_texto', $deTexto ?? null) }}">@endif

                    <div class="field-row">
                        <div class="field">
                            <label for="name">Nome *</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name">
                            @error('name') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="field">
                            <label for="email">E-mail *</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email">
                            @error('email') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="field">
                        <label for="phone">Telefone *</label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" placeholder="(11) 99999-9999" required maxlength="15" autocomplete="tel">
                        @error('phone') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label for="message">Mensagem *</label>
                        <textarea id="message" name="message" required minlength="50" maxlength="5000" placeholder="Conte-nos sobre seu projeto (mínimo 50 caracteres)...">{{ old('message', $recadoDaDemo ?? '') }}</textarea>
                        <div class="char-count"><span id="charCount">0</span>/5000</div>
                        @error('message') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <div class="consent">
                            <input type="checkbox" id="consent" name="consent" value="1" required>
                            <label for="consent">Autorizo o armazenamento dos meus dados para fins de contato, conforme a LGPD e a <a href="{{ url('/privacidade') }}">política de privacidade</a>.</label>
                        </div>
                        @error('consent') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    {{-- `name="captcha_token"`: o próprio widget mantém o campo
                         oculto com a solução — nenhum JS de cola no envio.
                         `auto="onload"` resolve o desafio sozinho ao abrir a
                         página; quando a pessoa termina de escrever, já passou. --}}
                    <div class="field captcha">
                        <altcha-widget
                            challenge="/captcha/desafio"
                            name="captcha_token"
                            language="pt-br"
                            auto="onload"
                            hidefooter
                            hidelogo
                        ></altcha-widget>
                        @error('captcha_token') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary">Enviar mensagem</button>
                        @error('envio') <span class="field-error" role="alert">{{ $message }}</span> @enderror
                    </div>
                </form>
            </div>
        </section>
@endsection

@section('flutuantes')
    {{-- Modal feedback --}}
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal">
            <div class="modal-icon" id="modalIcon"></div>
            <h3 id="modalTitle"></h3>
            <p id="modalText"></p>
            <button class="btn btn-primary" id="modalFechar">Fechar</button>
        </div>
    </div>
@endsection

@push('scripts')
<script src="/lancamentos.js" defer></script>
<script src="/topo.js" defer></script>
<script nonce="{{ $cspNonce ?? '' }}">
        // Máscara de telefone
        var phone = document.getElementById('phone');
        phone.addEventListener('input', function (e) {
            var v = e.target.value.replace(/\D/g, '');
            if (v.length > 11) v = v.slice(0, 11);
            if (v.length === 0) { e.target.value = ''; return; }
            if (v.length <= 2) e.target.value = '(' + v;
            else if (v.length <= 7) e.target.value = '(' + v.slice(0, 2) + ') ' + v.slice(2);
            else e.target.value = '(' + v.slice(0, 2) + ') ' + v.slice(2, 7) + '-' + v.slice(7);
        });

        // Contador de caracteres
        var msg = document.getElementById('message');
        var counter = document.getElementById('charCount');
        msg.addEventListener('input', function () { counter.textContent = msg.value.length; });
        counter.textContent = msg.value.length;

        // Trava o botão no envio.
        //
        // O envio é SÍNCRONO (o SMTP do Zoho acontece dentro da requisição), e
        // enquanto ele acontece a página fica parada, sem sinal nenhum de que
        // algo está em curso. A pessoa clica de novo — e o segundo POST manda
        // um segundo e-mail, porque abortar a navegação não desfaz o que o
        // servidor já fez. Reproduzido em 23/08/2026: dois cliques com 1,5 s
        // de intervalo, duas mensagens na caixa.
        //
        // Isto aqui resolve o caso comum e, principalmente, DÁ RESPOSTA: o
        // botão vira "Enviando…" e a pessoa para de clicar. O que garante que
        // não sai duplicado é o servidor (ver ContactController) — este
        // bloqueio não existe com JavaScript desligado.
        var formContato = document.querySelector('form[action="/contato"]');
        var btnEnviar = formContato.querySelector('button[type="submit"]');
        var textoBotao = btnEnviar.textContent;

        formContato.addEventListener('submit', function () {
            btnEnviar.disabled = true;
            btnEnviar.textContent = 'Enviando…';
            // Leitor de tela não anuncia troca de texto de botão sozinho.
            btnEnviar.setAttribute('aria-busy', 'true');
        });

        // Voltar pelo histórico devolve a página do cache do navegador com o
        // botão ainda travado — e aí ela não envia mais nada, sem dizer por
        // quê. `pageshow` é o único evento que dispara nesse caminho.
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) {
                btnEnviar.disabled = false;
                btnEnviar.textContent = textoBotao;
                btnEnviar.removeAttribute('aria-busy');
            }
        });

        // Modal de feedback (sessão)
        var overlay = document.getElementById('modalOverlay');
        var icon = document.getElementById('modalIcon');
        var modalTitle = document.getElementById('modalTitle');
        var modalText = document.getElementById('modalText');
        document.getElementById('modalFechar').addEventListener('click', function () {
            overlay.classList.remove('active');
        });

        @if (session('success'))
        overlay.classList.add('active');
        icon.className = 'modal-icon success';
        icon.innerHTML = '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
        modalTitle.textContent = 'Mensagem enviada!';
        modalText.textContent = '{{ session('success') }}';
        @endif

        @if (session('error'))
        overlay.classList.add('active');
        icon.className = 'modal-icon error';
        icon.innerHTML = '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
        modalTitle.textContent = 'Ops!';
        modalText.textContent = '{{ session('error') }}';
        @endif
</script>
@endpush
