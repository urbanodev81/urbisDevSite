<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UrbisDev — Software que resolve problemas reais</title>
    <meta name="description" content="Soluções digitais sob medida: SaaS, APIs, automações, dashboards e mobile. Sites e sistemas para imobiliárias, blogs e conteúdo.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=bricolage-grotesque:400,700,800|instrument-sans:400,500,600|jetbrains-mono:400,500&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Base — cidade à noite */
            --ink-900: #0B1220;
            --ink-800: #101A2D;
            --ink-700: #16233C;
            --line:    #22304B;

            /* Texto */
            --text-hi:  #EAF0FA;
            --text-mid: #A7B4C9;
            --text-low: #6B7A93;

            /* Acento da marca — âmbar "luz da cidade" */
            --amber-400: #FFC24B;
            --amber-500: #F5A524;
            --amber-600: #D18A0F;
            --amber-ink: #1A1204;

            /* Distritos (cores dos produtos — usar SÓ nos cards) */
            --d-synapse: #2DD4BF;
            --d-urb:     #8B5CF6;
            --d-ssb:     #E5484D;

            /* Semânticas */
            --ok:    #3DD68C;
            --warn:  #F5A524;
            --error: #E5484D;

            --r-btn: 10px;
            --r-card: 16px;
            --font-display: "Bricolage Grotesque", system-ui, sans-serif;
            --font-body: "Instrument Sans", system-ui, sans-serif;
            --font-mono: "JetBrains Mono", ui-monospace, monospace;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { background: var(--ink-900); color: var(--text-mid); font-family: var(--font-body); font-size: 1.0625rem; line-height: 1.7; -webkit-font-smoothing: antialiased; }
        ::selection { background: var(--amber-500); color: var(--amber-ink); }
        a { color: inherit; }
        img, svg { display: block; }
        .container { max-width: 1160px; margin-inline: auto; padding-inline: 24px; }
        @media (min-width: 768px) { .container { padding-inline: 32px; } }
        section { padding-block: clamp(72px, 10vw, 128px); scroll-margin-top: 72px; }
        h1, h2 { font-family: var(--font-display); color: var(--text-hi); line-height: 1.08; letter-spacing: -0.02em; }
        h1 { font-size: clamp(2.6rem, 6vw, 4.5rem); font-weight: 800; }
        h2 { font-size: clamp(1.9rem, 3.5vw, 2.6rem); font-weight: 700; }
        h3 { font-family: var(--font-body); font-weight: 600; font-size: 1.25rem; color: var(--text-hi); }
        :focus-visible { outline: 2px solid var(--amber-500); outline-offset: 3px; border-radius: 4px; }

        /* ---------- eyebrow ---------- */
        .eyebrow { display: inline-flex; align-items: center; gap: 10px; font-family: var(--font-mono); font-size: .75rem; letter-spacing: .14em; text-transform: uppercase; color: var(--text-low); }
        .eyebrow::before { content: ""; width: 6px; height: 6px; background: var(--amber-500); flex: none; }

        /* ---------- botões ---------- */
        .btn { display: inline-flex; align-items: center; gap: 8px; font-weight: 600; font-size: 1rem; padding: 12px 22px; border-radius: var(--r-btn); text-decoration: none; border: 1px solid transparent; transition: background .18s ease-out, border-color .18s ease-out, color .18s ease-out; cursor: pointer; font-family: var(--font-body); }
        .btn-primary { background: var(--amber-500); color: var(--amber-ink); }
        .btn-primary:hover { background: var(--amber-400); }
        .btn-primary:active { background: var(--amber-600); }
        .btn-ghost { background: transparent; border-color: var(--line); color: var(--text-hi); }
        .btn-ghost:hover { border-color: var(--amber-500); }

        /* ---------- header ---------- */
        header { position: sticky; top: 0; z-index: 50; background: rgba(11, 18, 32, .82); backdrop-filter: blur(12px); border-bottom: 1px solid var(--line); }
        .nav { display: flex; align-items: center; justify-content: space-between; height: 68px; }
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .brand-mark { width: 34px; height: 34px; border: 1.5px solid var(--amber-500); border-radius: 8px; display: grid; place-items: center; font-family: var(--font-mono); font-size: .72rem; font-weight: 500; color: var(--amber-500); }
        .brand-name { font-family: var(--font-display); font-weight: 700; font-size: 1.15rem; color: var(--text-hi); }
        .brand-name span { color: var(--amber-500); }
        .nav-links { display: none; align-items: center; gap: 28px; list-style: none; }
        .nav-links a { text-decoration: none; font-size: .95rem; font-weight: 500; color: var(--text-mid); transition: color .16s ease-out; }
        .nav-links a:hover { color: var(--text-hi); }
        .nav-cta { display: none; }
        .nav-toggle { background: none; border: 1px solid var(--line); border-radius: 8px; padding: 8px; color: var(--text-hi); cursor: pointer; }
        @media (min-width: 880px) {
            .nav-links, .nav-cta { display: inline-flex; }
            .nav-toggle { display: none; }
        }
        .mobile-menu { display: flex; flex-direction: column; gap: 4px; padding: 0 24px; border-top: 1px solid transparent; max-height: 0; opacity: 0; visibility: hidden; overflow: hidden; transition: max-height .25s ease, opacity .25s ease, padding .25s ease, border-color .25s ease, visibility 0s linear .25s; }
        .mobile-menu.open { max-height: 340px; opacity: 1; visibility: visible; padding: 16px 24px 24px; border-top-color: var(--line); transition: max-height .25s ease, opacity .25s ease, padding .25s ease, border-color .25s ease, visibility 0s; }
        @media (min-width: 880px) { .mobile-menu { display: none; } }
        .mobile-menu a { text-decoration: none; padding: 10px 4px; font-weight: 500; color: var(--text-mid); }
        .mobile-menu a:hover { color: var(--text-hi); }
        .nav-toggle svg { transition: transform .25s ease; }
        .nav-toggle[aria-expanded="true"] svg { transform: rotate(90deg); }

        /* ---------- hero + grade de janelas ---------- */
        .hero { position: relative; overflow: hidden; padding-block: clamp(88px, 12vw, 150px); }
        .citygrid { position: absolute; inset: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(26px, 1fr)); grid-auto-rows: 26px; gap: 0; opacity: .9; pointer-events: none;
            -webkit-mask-image: radial-gradient(ellipse 90% 85% at 50% 20%, #000 30%, transparent 78%);
            mask-image: radial-gradient(ellipse 90% 85% at 50% 20%, #000 30%, transparent 78%); }
        .citygrid i { border-right: 1px solid rgba(34, 48, 75, .5); border-bottom: 1px solid rgba(34, 48, 75, .5); }
        .citygrid i.lit { position: relative; }
        .citygrid i.lit::after { content: ""; position: absolute; inset: 9px; background: var(--amber-500); opacity: 0; animation: window var(--dur, 7s) ease-in-out var(--delay, 0s) infinite; }
        @keyframes window { 0%, 100% { opacity: 0; } 12% { opacity: .85; } 55% { opacity: .5; } 70% { opacity: 0; } }
        .hero-inner { position: relative; max-width: 820px; }
        .hero h1 { margin: 20px 0 22px; }
        .hero h1 em { font-style: normal; color: var(--amber-500); }
        .hero p { max-width: 56ch; font-size: 1.15rem; }
        .hero-ctas { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 36px; }
        .hero-meta { margin-top: 56px; display: flex; flex-wrap: wrap; gap: 12px 32px; font-family: var(--font-mono); font-size: .75rem; letter-spacing: .12em; text-transform: uppercase; color: var(--text-low); }
        .hero-meta span { display: inline-flex; align-items: center; gap: 8px; }
        .hero-meta svg { color: var(--amber-500); }

        /* ---------- seções / cards ---------- */
        .section-head { max-width: 640px; margin-bottom: clamp(40px, 6vw, 64px); }
        .section-head h2 { margin: 16px 0 14px; }
        .districts { display: grid; gap: 20px; }
        @media (min-width: 720px) { .districts { grid-template-columns: repeat(2, 1fr); } }
        .district { position: relative; background: var(--ink-800); border: 1px solid var(--line); border-radius: var(--r-card); padding: 28px 26px 26px; display: flex; flex-direction: column; gap: 14px; transition: transform .2s ease-out, border-color .2s ease-out; color: var(--text-mid); }
        .district::before { content: ""; position: absolute; top: 0; left: 24px; right: 24px; height: 3px; border-radius: 0 0 3px 3px; background: var(--dc, var(--amber-500)); }
        .district:hover { transform: translateY(-3px); border-color: color-mix(in srgb, var(--dc, var(--amber-500)) 40%, var(--line)); }
        .district .tag { font-family: var(--font-mono); font-size: .72rem; letter-spacing: .14em; text-transform: uppercase; color: var(--text-low); }
        .district .tag b { color: var(--dc, var(--amber-500)); font-weight: 500; }
        .district h3 { font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; }
        .district p { font-size: .98rem; flex: 1; }
        .district .card-icon { color: var(--dc, var(--amber-500)); }
        .chip { display: inline-flex; align-items: center; gap: 7px; font-family: var(--font-mono); font-size: .7rem; letter-spacing: .1em; text-transform: uppercase; border: 1px solid var(--line); border-radius: 999px; padding: 5px 12px; color: var(--text-mid); }
        .chip::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: var(--ok); }
        .chip--dev::before { background: var(--warn); }
        .chip--plan::before { background: var(--d-urb); }
        .chip--dream::before { background: var(--text-low); }

        /* ---------- roadmap (próximos passos) ---------- */
        .roadmap { display: grid; gap: 20px; }
        .roadmap-card { position: relative; border: 1px dashed var(--line); border-radius: var(--r-card); padding: clamp(28px, 4vw, 44px); display: flex; flex-wrap: wrap; gap: 24px; align-items: center; justify-content: space-between; overflow: hidden; }
        .roadmap-card::before { content: ""; position: absolute; top: 0; left: 24px; right: 24px; height: 3px; border-radius: 0 0 3px 3px; background: var(--dc, var(--line)); opacity: .7; }
        .roadmap-card h3 { font-family: var(--font-display); font-size: 1.4rem; display: flex; align-items: center; gap: 12px; margin-top: 12px; }
        .roadmap-card h3 svg { color: var(--dc, var(--text-low)); flex: none; }
        .roadmap-card p { max-width: 52ch; margin-top: 6px; font-size: .98rem; }

        /* ---------- contato ---------- */
        .contact { border-top: 1px solid var(--line); background: var(--ink-800); }
        .contact-grid { display: grid; gap: 48px; }
        @media (min-width: 880px) { .contact-grid { grid-template-columns: 1fr 1.2fr; gap: 80px; } }
        .contact-info p { margin: 14px 0 28px; max-width: 44ch; }
        .contact-line { display: flex; align-items: center; gap: 12px; font-size: .98rem; color: var(--text-hi); margin-bottom: 12px; text-decoration: none; }
        .contact-line svg { color: var(--amber-500); flex: none; }
        .contact-line:hover { color: var(--amber-400); }
        form { display: grid; gap: 18px; }
        .field { display: grid; gap: 7px; }
        .field label { font-size: .9rem; font-weight: 600; color: var(--text-hi); }
        .field input, .field textarea { background: var(--ink-900); border: 1px solid var(--line); border-radius: var(--r-btn); padding: 12px 14px; color: var(--text-hi); font: inherit; font-size: 1rem; transition: border-color .16s ease-out; width: 100%; }
        .field input::placeholder, .field textarea::placeholder { color: var(--text-low); }
        .field input:focus, .field textarea:focus { outline: none; border-color: var(--amber-500); }
        .field textarea { min-height: 130px; resize: vertical; }
        .field-row { display: grid; gap: 18px; }
        @media (min-width: 560px) { .field-row { grid-template-columns: 1fr 1fr; } }
        .field-error { color: var(--error); font-size: .82rem; }
        .consent { display: flex; gap: 10px; align-items: flex-start; justify-content: flex-start; font-size: .88rem; color: var(--text-low); text-align: left; }
        .consent input[type="checkbox"] { width: 16px; height: 16px; flex: none; margin-top: 4px; padding: 0; accent-color: var(--amber-500); cursor: pointer; }
        .consent label { font-weight: 400; font-size: .88rem; color: var(--text-low); cursor: pointer; }
        .char-count { font-family: var(--font-mono); font-size: .72rem; color: var(--text-low); text-align: right; }

        /* ---------- footer ---------- */
        footer { border-top: 1px solid var(--line); padding: 40px 0; background: var(--ink-800); }
        .foot-grid { display: flex; flex-wrap: wrap; gap: 24px 40px; align-items: center; justify-content: space-between; }
        .foot-grid p { font-size: .9rem; color: var(--text-low); }
        .foot-links { display: flex; flex-wrap: wrap; gap: 22px; list-style: none; }
        .foot-links a { font-size: .9rem; text-decoration: none; color: var(--text-mid); }
        .foot-links a:hover { color: var(--text-hi); }
        .social-bar { display: flex; gap: 12px; }
        .social-bar a { width: 38px; height: 38px; border: 1px solid var(--line); border-radius: 50%; display: grid; place-items: center; color: var(--text-mid); transition: border-color .18s ease-out, color .18s ease-out, transform .18s ease-out; }
        .social-bar a:hover { transform: translateY(-2px); }

        /* cores oficiais das marcas (hex fixo de propósito: são cores DELES, não nossas) */
        .s-wa { background: #25d366 !important; border-color: #25d366 !important; color: #fff !important; }
        .s-ig { background: radial-gradient(circle at 30% 110%, #fdf497 0%, #fd5949 45%, #d6249f 60%, #285aeb 90%) !important; border-color: transparent !important; color: #fff !important; }
        .s-li { background: #0a66c2 !important; border-color: #0a66c2 !important; color: #fff !important; }
        .s-mail { background: var(--amber-500) !important; border-color: var(--amber-500) !important; color: var(--ink-900) !important; }

        /* ---------- modal de feedback ---------- */
        .modal-overlay { position: fixed; inset: 0; z-index: 9999; background: rgba(6, 10, 20, .7); backdrop-filter: blur(3px); display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity .3s ease-out; }
        .modal-overlay.active { opacity: 1; pointer-events: auto; }
        .modal { background: var(--ink-800); border: 1px solid var(--line); border-radius: var(--r-card); padding: 40px 32px 32px; max-width: 440px; width: 90%; text-align: center; transform: translateY(20px); transition: transform .3s ease-out; }
        .modal-overlay.active .modal { transform: translateY(0); }
        .modal-icon { width: 64px; height: 64px; border-radius: 50%; border: 1px solid var(--line); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px; }
        .modal-icon.success { color: var(--ok); border-color: color-mix(in srgb, var(--ok) 40%, var(--line)); }
        .modal-icon.error { color: var(--error); border-color: color-mix(in srgb, var(--error) 40%, var(--line)); }
        .modal h3 { font-family: var(--font-display); font-size: 1.35rem; font-weight: 700; margin-bottom: 8px; }
        .modal p { color: var(--text-mid); font-size: .98rem; margin-bottom: 24px; }

        /* ---------- voltar ao topo ---------- */
        .back-to-top { position: fixed; bottom: 24px; right: 24px; z-index: 999; width: 44px; height: 44px; background: var(--ink-800); color: var(--amber-500); border: 1px solid var(--line); border-radius: 50%; cursor: pointer; opacity: 0; transform: translateY(10px); transition: opacity .3s ease-out, transform .3s ease-out, border-color .18s ease-out; pointer-events: none; display: flex; align-items: center; justify-content: center; }
        .back-to-top.visible { opacity: 1; transform: translateY(0); pointer-events: auto; }
        .back-to-top:hover { border-color: var(--amber-500); }

        /* ---------- pilha social flutuante (some quando a do rodapé aparece) ---------- */
        .social-float { position: fixed; bottom: 84px; right: 24px; z-index: 999; display: flex; flex-direction: column; gap: 10px; opacity: 0; transform: translateY(10px); transition: opacity .3s ease-out, transform .3s ease-out; pointer-events: none; }
        .social-float.visible { opacity: 1; transform: translateY(0); pointer-events: auto; }
        .social-float a { width: 44px; height: 44px; background: var(--ink-800); border: 1px solid var(--line); border-radius: 50%; display: grid; place-items: center; color: var(--text-mid); transition: transform .18s ease-out; box-shadow: 0 4px 14px rgba(0,0,0,.35); }
        .social-float a:hover { transform: translateY(-2px); }

        /* ---------- acessibilidade (FAB + painel) ---------- */
        .a11y { position: fixed; bottom: 24px; left: 24px; z-index: 999; }
        .a11y-fab { width: 44px; height: 44px; background: var(--ink-800); color: var(--amber-500); border: 1px solid var(--line); border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: border-color .18s ease-out; }
        .a11y-fab:hover, .a11y-fab[aria-expanded="true"] { border-color: var(--amber-500); }
        .a11y-panel { position: absolute; bottom: 56px; left: 0; width: min(300px, calc(100vw - 48px)); background: var(--ink-800); border: 1px solid var(--line); border-radius: var(--r-card); padding: 18px; display: grid; gap: 10px; box-shadow: 0 12px 32px rgba(6, 10, 20, .55); }
        .a11y-panel[hidden] { display: none; }
        .a11y-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 4px; }
        .a11y-head strong { font-family: var(--font-display); font-size: 1rem; font-weight: 700; color: var(--text-hi); }
        .a11y-reset { background: none; border: none; font-family: var(--font-mono); font-size: .7rem; letter-spacing: .1em; text-transform: uppercase; color: var(--text-low); cursor: pointer; padding: 4px; }
        .a11y-reset:hover { color: var(--amber-400); }
        .a11y-label { font-size: .82rem; font-weight: 600; color: var(--text-hi); }
        .a11y-fonts { display: flex; gap: 8px; }
        .a11y-fonts button { flex: 1; background: var(--ink-900); border: 1px solid var(--line); border-radius: var(--r-btn); padding: 8px 0; color: var(--text-mid); font-family: var(--font-body); font-weight: 600; cursor: pointer; transition: border-color .16s ease-out, color .16s ease-out; }
        .a11y-fonts button:nth-child(1) { font-size: .85rem; }
        .a11y-fonts button:nth-child(2) { font-size: .98rem; }
        .a11y-fonts button:nth-child(3) { font-size: 1.1rem; }
        .a11y-fonts button[aria-pressed="true"] { border-color: var(--amber-500); color: var(--amber-400); }
        .a11y-opt { display: flex; align-items: center; justify-content: space-between; gap: 12px; background: var(--ink-900); border: 1px solid var(--line); border-radius: var(--r-btn); padding: 10px 12px; color: var(--text-mid); font-family: var(--font-body); font-size: .9rem; cursor: pointer; text-align: left; transition: border-color .16s ease-out, color .16s ease-out; }
        .a11y-opt::after { content: ""; width: 10px; height: 10px; border-radius: 50%; border: 1px solid var(--text-low); flex: none; transition: background .16s ease-out, border-color .16s ease-out; }
        .a11y-opt:hover { border-color: color-mix(in srgb, var(--amber-500) 40%, var(--line)); }
        .a11y-opt[aria-pressed="true"] { color: var(--text-hi); border-color: color-mix(in srgb, var(--amber-500) 45%, var(--line)); }
        .a11y-opt[aria-pressed="true"]::after { background: var(--amber-500); border-color: var(--amber-500); }

        /* efeitos das opções (classes no <html>) */
        html.a11y-font-1 { font-size: 112.5%; }
        html.a11y-font-2 { font-size: 125%; }
        html.a11y-underline a { text-decoration: underline !important; }
        html.a11y-spacing :is(p, li, a, span, label, h1, h2, h3, button, input, textarea, strong, b, em) { letter-spacing: .12em !important; word-spacing: .16em !important; line-height: 1.8 !important; }
        html.a11y-pause *, html.a11y-pause *::before, html.a11y-pause *::after { transition-duration: 0s !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; }
        html.a11y-pause .citygrid i.lit::after { animation: none !important; opacity: .35; }

        /* ---------- reveal + reduced motion ---------- */
        /* Scroll de âncora/topo fica suave SEMPRE (regra da casa, como no
           site original): reduced-motion e "pausar animações" desligam só
           as animações decorativas, não a rolagem de navegação. */
        .reveal { opacity: 0; transform: translateY(12px); transition: opacity .5s ease-out, transform .5s ease-out; }
        .reveal.in { opacity: 1; transform: none; }
        @media (prefers-reduced-motion: reduce) {
            .reveal { opacity: 1; transform: none; transition: none; }
            .citygrid i.lit::after { animation: none; opacity: .35; }
            .mobile-menu, .mobile-menu.open, .nav-toggle svg { transition: none; }
        }
    </style>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>

    {{-- Header --}}
    <header>
        <div class="container nav">
            <a class="brand" href="#top" aria-label="UrbisDev — início">
                <span class="brand-mark">UD</span>
                <span class="brand-name">Urbis<span>Dev</span></span>
            </a>
            <ul class="nav-links">
                <li><a href="#fazemos">O que fazemos</a></li>
                <li><a href="#proximos">Próximos passos</a></li>
                <li><a href="#contato">Contato</a></li>
            </ul>
            <a class="btn btn-primary nav-cta" href="#contato">Fale Conosco</a>
            <button class="nav-toggle" aria-label="Abrir menu" aria-expanded="false" onclick="toggleMenu(this)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></svg>
            </button>
        </div>
        <nav class="mobile-menu" id="mobileMenu">
            <a href="#fazemos" onclick="closeMenu()">O que fazemos</a>
            <a href="#proximos" onclick="closeMenu()">Próximos passos</a>
            <a href="#contato" onclick="closeMenu()">Contato</a>
            <a class="btn btn-primary" style="margin-top:10px;justify-content:center" href="#contato" onclick="closeMenu()">Fale Conosco</a>
        </nav>
    </header>

    <main id="top">

        {{-- Hero --}}
        <section class="hero" aria-label="Apresentação">
            <div class="citygrid" id="citygrid" aria-hidden="true"></div>
            <div class="container hero-inner">
                <span class="eyebrow">SaaS · APIs · Automação · Dashboards · Mobile</span>
                <h1>Software que resolve <em>problemas reais</em>.</h1>
                <p>Desenvolvemos SaaS, APIs, automações, dashboards e aplicativos mobile sob medida para o seu negócio.</p>
                <div class="hero-ctas">
                    <a class="btn btn-primary" href="#contato">Fale Conosco
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                    <a class="btn btn-ghost" href="#fazemos">O que fazemos</a>
                </div>
            </div>
        </section>

        {{-- O que fazemos --}}
        <section id="fazemos">
            <div class="container">
                <div class="section-head reveal">
                    <span class="eyebrow">O que fazemos</span>
                    <h2>Soluções digitais sob medida para o seu negócio.</h2>
                </div>
                <div class="districts">
                    <div class="district reveal" style="--dc:var(--d-urb)">
                        <span class="tag">Frente · <b>Imobiliário</b></span>
                        <svg class="card-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-4h6v4"/><path d="M9 10h.01"/><path d="M15 10h.01"/><path d="M9 14h.01"/><path d="M15 14h.01"/></svg>
                        <h3>Imobiliárias</h3>
                        <p>Sites institucionais e sistemas de gestão imobiliária. Portais de imóveis com busca avançada, integração com portais e CRM próprio.</p>
                    </div>
                    <div class="district reveal" style="--dc:var(--amber-500)">
                        <span class="tag">Frente · <b>Conteúdo</b></span>
                        <svg class="card-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        <h3>Blogs &amp; Conteúdo</h3>
                        <p>Blogs corporativos com SEO otimizado, gestão de conteúdo simplificada e design moderno para engajar seu público.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Próximos passos --}}
        <section id="proximos">
            <div class="container">
                <div class="section-head reveal">
                    <span class="eyebrow">Próximos passos</span>
                    <h2>O que está por vir na UrbisDev.</h2>
                </div>
                <div class="roadmap reveal">
                    <div class="roadmap-card" style="--dc:var(--d-ssb)">
                        <div>
                            <span class="eyebrow">Próxima obra</span>
                            <h3>
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                                Plataformas white-label
                            </h3>
                            <p>Plataformas SaaS multi-tenant com a marca do cliente: cada operação com identidade própria e dados isolados, sobre uma base única — prontas para diferentes áreas de negócio.</p>
                        </div>
                        <span class="chip chip--plan" style="align-self:center">Em planejamento</span>
                    </div>
                    <div class="roadmap-card" style="--dc:var(--d-synapse)">
                        <div>
                            <span class="eyebrow">No horizonte</span>
                            <h3>
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="11" x2="10" y2="11"/><line x1="8" y1="9" x2="8" y2="13"/><line x1="15" y1="12" x2="15.01" y2="12"/><line x1="18" y1="10" x2="18.01" y2="10"/><path d="M17.32 5H6.68a4 4 0 0 0-3.978 3.59c-.006.052-.01.101-.017.152C2.604 9.416 2 14.456 2 16a3 3 0 0 0 3 3c1 0 1.5-.5 2-1l1.414-1.414A2 2 0 0 1 9.828 16h4.344a2 2 0 0 1 1.414.586L17 18c.5.5 1 1 2 1a3 3 0 0 0 3-3c0-1.545-.604-6.584-.685-7.258-.007-.05-.011-.1-.017-.151A4 4 0 0 0 17.32 5z"/></svg>
                                Games
                            </h3>
                            <p>Jogos casuais e educativos para web e mobile. Experiências interativas que unem diversão e propósito.</p>
                        </div>
                        <span class="chip chip--dream" style="align-self:center">O sonho</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Contato --}}
        <section id="contato" class="contact">
            <div class="container contact-grid">
                <div class="contact-info reveal">
                    <span class="eyebrow">Contato</span>
                    <h2 style="margin-top:16px">Tem um projeto em mente? Vamos conversar.</h2>
                    <p>Conte pra gente o que você precisa. Preencha o formulário e retornamos o contato.</p>
                    <a class="contact-line" href="mailto:contato@urbisdev.tech">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        contato@urbisdev.tech
                    </a>
                    <a class="contact-line" href="https://urbisdev.tech">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
                        urbisdev.tech
                    </a>
                </div>

                <form action="/contato" method="POST" class="reveal">
                    @csrf

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
                        <textarea id="message" name="message" required minlength="50" maxlength="5000" placeholder="Conte-nos sobre seu projeto (mínimo 50 caracteres)...">{{ old('message') }}</textarea>
                        <div class="char-count"><span id="charCount">0</span>/5000</div>
                        @error('message') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <div class="consent">
                            <input type="checkbox" id="consent" name="consent" value="1" required>
                            <label for="consent">Autorizo o armazenamento dos meus dados para fins de contato, conforme a LGPD.</label>
                        </div>
                        @error('consent') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field" style="justify-items:center">
                        <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-theme="dark"></div>
                        @error('turnstile_token') <span class="field-error" style="text-align:center">{{ $message }}</span> @enderror
                    </div>

                    <input type="hidden" name="turnstile_token" id="turnstile-token">

                    <div>
                        <button type="submit" class="btn btn-primary">Enviar mensagem</button>
                    </div>
                </form>
            </div>
        </section>
    </main>

    {{-- Footer --}}
    <footer>
        <div class="container foot-grid">
            <a class="brand" href="#top" aria-label="UrbisDev">
                <span class="brand-mark">UD</span>
                <span class="brand-name">Urbis<span>Dev</span></span>
            </a>
            <ul class="foot-links">
                <li><a href="#fazemos">O que fazemos</a></li>
                <li><a href="#proximos">Próximos passos</a></li>
                <li><a href="#contato">Contato</a></li>
            </ul>
            <div class="social-bar">
                <a class="s-wa" href="https://wa.me/5511976862551" target="_blank" rel="noopener" aria-label="WhatsApp">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21l1.65-4.95A8.96 8.96 0 0 1 3 11a9 9 0 1 1 9 9 8.96 8.96 0 0 1-5.05-1.65L3 21z"/><path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1"/></svg>
                </a>
                {{-- Instagram/LinkedIn visíveis pra avaliação (31/07); trocar o
                     href="#" pelas URLs reais quando os perfis existirem. --}}
                <a class="s-ig" href="#" aria-label="Instagram">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                </a>
                <a class="s-li" href="#" aria-label="LinkedIn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                </a>
                <a class="s-mail" href="mailto:contato@urbisdev.tech" aria-label="E-mail">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                </a>
            </div>
            <p>&copy; {{ date('Y') }} UrbisDev. Todos os direitos reservados.</p>
        </div>
    </footer>

    {{-- Modal feedback --}}
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal">
            <div class="modal-icon" id="modalIcon"></div>
            <h3 id="modalTitle"></h3>
            <p id="modalText"></p>
            <button class="btn btn-primary" onclick="document.getElementById('modalOverlay').classList.remove('active')">Fechar</button>
        </div>
    </div>

    {{-- Acessibilidade --}}
    <div class="a11y" id="a11yWidget">
        <div class="a11y-panel" id="a11yPanel" role="dialog" aria-label="Opções de acessibilidade" hidden>
            <div class="a11y-head">
                <strong>Acessibilidade</strong>
                <button type="button" class="a11y-reset" id="a11yReset">Redefinir</button>
            </div>
            <span class="a11y-label" id="a11yFontLabel">Tamanho do texto</span>
            <div class="a11y-fonts" role="group" aria-labelledby="a11yFontLabel">
                <button type="button" data-a11y-font="0" aria-pressed="true" aria-label="Tamanho padrão">A</button>
                <button type="button" data-a11y-font="1" aria-pressed="false" aria-label="Texto maior">A+</button>
                <button type="button" data-a11y-font="2" aria-pressed="false" aria-label="Texto muito maior">A++</button>
            </div>
            <button type="button" class="a11y-opt" data-a11y-opt="underline" aria-pressed="false">Sublinhar links</button>
            <button type="button" class="a11y-opt" data-a11y-opt="spacing" aria-pressed="false">Espaçamento de texto</button>
            <button type="button" class="a11y-opt" data-a11y-opt="pause" aria-pressed="false">Pausar animações</button>
        </div>
        <button type="button" class="a11y-fab" id="a11yFab" aria-label="Opções de acessibilidade" aria-expanded="false" aria-controls="a11yPanel">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="7" r="1" fill="currentColor" stroke="none"/><path d="M7.5 10.2c3 .75 6 .75 9 0"/><path d="M12 11v3.2"/><path d="m10 17.5 2-3.3 2 3.3"/></svg>
        </button>
    </div>

    {{-- Pilha social flutuante (espelho da barra do rodapé) --}}
    <div class="social-float" id="socialFloat" aria-label="Redes sociais">
        <a class="s-wa" href="https://wa.me/5511976862551" target="_blank" rel="noopener" aria-label="WhatsApp">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21l1.65-4.95A8.96 8.96 0 0 1 3 11a9 9 0 1 1 9 9 8.96 8.96 0 0 1-5.05-1.65L3 21z"/><path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1"/></svg>
        </a>
        <a class="s-ig" href="#" aria-label="Instagram">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
        </a>
        <a class="s-li" href="#" aria-label="LinkedIn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
        </a>
        <a class="s-mail" href="mailto:contato@urbisdev.tech" aria-label="E-mail">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
        </a>
    </div>

    {{-- Back to top --}}
    <button class="back-to-top" id="backToTop" aria-label="Voltar ao topo">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
    </button>

    <script>
        // Grade de janelas acesas (assinatura da marca)
        (function () {
            var grid = document.getElementById('citygrid');
            var cols = Math.ceil(window.innerWidth / 26) + 1;
            var rows = Math.ceil(Math.min(window.innerHeight, 720) / 26) + 1;
            var total = cols * rows;
            var frag = document.createDocumentFragment();
            for (var i = 0; i < total; i++) {
                var cell = document.createElement('i');
                if (Math.random() < 0.045) { // ~4,5% das janelas acendem
                    cell.className = 'lit';
                    cell.style.setProperty('--delay', (Math.random() * 12).toFixed(1) + 's');
                    cell.style.setProperty('--dur', (6 + Math.random() * 8).toFixed(1) + 's');
                }
                frag.appendChild(cell);
            }
            grid.appendChild(frag);
        })();

        // Reveal on scroll
        (function () {
            if (!('IntersectionObserver' in window)) {
                document.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('in'); });
                return;
            }
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
                });
            }, { threshold: 0.12 });
            document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });
        })();

        // Menu mobile
        function toggleMenu(btn) {
            var m = document.getElementById('mobileMenu');
            var open = m.classList.toggle('open');
            btn.setAttribute('aria-expanded', open);
        }
        function closeMenu() {
            document.getElementById('mobileMenu').classList.remove('open');
            document.querySelector('.nav-toggle').setAttribute('aria-expanded', 'false');
        }

        // Voltar ao topo — suave sempre, como no site original
        var backBtn = document.getElementById('backToTop');
        backBtn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        window.addEventListener('scroll', function () {
            backBtn.classList.toggle('visible', window.scrollY > 400);
        });

        // Pilha social flutuante: visível enquanto a barra do rodapé está fora da tela
        (function () {
            var socialFloat = document.getElementById('socialFloat');
            var footerBar = document.querySelector('footer .social-bar');
            if (!socialFloat || !footerBar) return;
            if (!('IntersectionObserver' in window)) { socialFloat.classList.add('visible'); return; }
            new IntersectionObserver(function (entries) {
                socialFloat.classList.toggle('visible', !entries[0].isIntersecting);
            }).observe(footerBar);
        })();

        // Widget de acessibilidade
        (function () {
            var KEY = 'urbis-a11y';
            var root = document.documentElement;
            var fab = document.getElementById('a11yFab');
            var panel = document.getElementById('a11yPanel');
            var widget = document.getElementById('a11yWidget');
            var state = { font: 0, underline: false, spacing: false, pause: false };
            try {
                var saved = JSON.parse(localStorage.getItem(KEY));
                if (saved && typeof saved === 'object') {
                    state.font = [0, 1, 2].indexOf(saved.font) !== -1 ? saved.font : 0;
                    state.underline = !!saved.underline;
                    state.spacing = !!saved.spacing;
                    state.pause = !!saved.pause;
                }
            } catch (e) { /* localStorage indisponível: segue sem persistência */ }

            function apply() {
                root.classList.toggle('a11y-font-1', state.font === 1);
                root.classList.toggle('a11y-font-2', state.font === 2);
                root.classList.toggle('a11y-underline', state.underline);
                root.classList.toggle('a11y-spacing', state.spacing);
                root.classList.toggle('a11y-pause', state.pause);
                panel.querySelectorAll('[data-a11y-font]').forEach(function (b) {
                    b.setAttribute('aria-pressed', String(Number(b.getAttribute('data-a11y-font')) === state.font));
                });
                panel.querySelectorAll('[data-a11y-opt]').forEach(function (b) {
                    b.setAttribute('aria-pressed', String(!!state[b.getAttribute('data-a11y-opt')]));
                });
                try { localStorage.setItem(KEY, JSON.stringify(state)); } catch (e) { /* noop */ }
            }

            panel.querySelectorAll('[data-a11y-font]').forEach(function (b) {
                b.addEventListener('click', function () {
                    state.font = Number(b.getAttribute('data-a11y-font'));
                    apply();
                });
            });
            panel.querySelectorAll('[data-a11y-opt]').forEach(function (b) {
                b.addEventListener('click', function () {
                    var k = b.getAttribute('data-a11y-opt');
                    state[k] = !state[k];
                    apply();
                });
            });
            document.getElementById('a11yReset').addEventListener('click', function () {
                state = { font: 0, underline: false, spacing: false, pause: false };
                apply();
            });

            function setOpen(open) {
                panel.hidden = !open;
                fab.setAttribute('aria-expanded', String(open));
            }
            fab.addEventListener('click', function () { setOpen(panel.hidden); });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !panel.hidden) { setOpen(false); fab.focus(); }
            });
            document.addEventListener('click', function (e) {
                if (!panel.hidden && !widget.contains(e.target)) setOpen(false);
            });

            apply();
        })();

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

        // Turnstile: copia o token pro campo hidden no submit
        document.querySelector('form[action="/contato"]').addEventListener('submit', function () {
            var token = document.querySelector('[name="cf-turnstile-response"]');
            if (token) {
                document.getElementById('turnstile-token').value = token.value;
            }
        });

        // Modal de feedback (sessão)
        var overlay = document.getElementById('modalOverlay');
        var icon = document.getElementById('modalIcon');
        var modalTitle = document.getElementById('modalTitle');
        var modalText = document.getElementById('modalText');

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
</body>
</html>
