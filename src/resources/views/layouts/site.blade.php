{{--
    O casco do site — head, header, rodapé, flutuantes e o JS de navegação.

    POR QUE ELE EXISTE (23/08/2026)

    Até aqui `welcome.blade.php` e `como-trabalhamos.blade.php` carregavam
    CADA UMA a sua cópia do casco: o mesmo `<head>`, o mesmo header, o mesmo
    rodapé, o mesmo widget de acessibilidade. E a duplicação já tinha cobrado:

    - o widget de acessibilidade existia DUAS vezes — inteiro dentro da home e
      de novo em `partials/acessibilidade.blade.php` (o próprio comentário do
      partial dizia que era cópia e que a resolução era esta aqui);
    - as correções de alvo de toque de 44px foram feitas só na página nova. A
      home ficou com `.nav-toggle` de 8px de padding, link de menu sem altura
      mínima e ícone de rede social de 38px — a "dívida da home" da fila era
      literalmente isto: uma correção que não atravessou a cópia;
    - o rodapé da home tinha a barra de redes sociais e o da outra página não;
    - "Próximos passos" existia no menu de uma e não no da outra.

    Nada disso era decisão. Era cópia envelhecendo em ritmos diferentes.

    COMO USAR

        @extends('layouts.site')
        @section('titulo', 'Título — UrbisDev')
        @section('descricao', 'Uma frase para o buscador.')
        @push('estilos') <style>…só o CSS DESTA página…</style> @endpush
        @section('conteudo') … @endsection
        @push('scripts') <script>…só o JS DESTA página…</script> @endpush

    O layout decide sozinho, pela rota, se os links do menu são âncoras da
    própria página (`#contato`) ou voltam para a home (`/#contato`).
--}}
@php
    $naHome = request()->path() === '/';
    // Base dos links de âncora: vazia na home (a âncora é da própria página),
    // absoluta fora dela. Era a única diferença real entre os dois headers.
    $base = $naHome ? '' : url('/');
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{--
        Tema, ANTES de qualquer CSS (CORE §2 item 2).

        Se o tema fosse aplicado depois, quem escolheu o claro veria a página
        piscar escura primeiro — e isso é pior que não ter os dois temas. O
        script é síncrono e minúsculo de propósito.

        Três estados, como manda o contrato: 'claro', 'escuro' e — quando não
        há nada salvo — o que o sistema pedir. Esta é a ÚNICA regra de
        resolução do tema no site: o botão do painel de acessibilidade grava a
        mesma chave e não reimplementa a decisão.
    --}}
    <script>
        (function () {
            var claro = false;
            try {
                var t = localStorage.getItem('urbis-tema');
                claro = t ? t === 'claro'
                          : window.matchMedia('(prefers-color-scheme: light)').matches;
            } catch (e) { /* storage bloqueado: fica no escuro, que é o padrão da marca */ }
            document.documentElement.classList.toggle('claro', claro);
        })();
    </script>

    <title>@yield('titulo', 'UrbisDev — Software que resolve problemas reais')</title>
    <meta name="description" content="@yield('descricao')">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=bricolage-grotesque:400,700,800|instrument-sans:400,500,600|jetbrains-mono:400,500&display=swap" rel="stylesheet">

    <style>
        @include('partials.tokens')

        /* ================= CASCO — vale para todas as páginas ================= */

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { background: var(--bg); color: var(--fg-muted); font-family: var(--font-body); font-size: 1.0625rem; line-height: 1.7; -webkit-font-smoothing: antialiased; }
        ::selection { background: var(--accent); color: var(--fg-on-accent); }
        a { color: inherit; }
        img, svg { display: block; }
        .container { max-width: 1160px; margin-inline: auto; padding-inline: 24px; }
        @media (min-width: 768px) { .container { padding-inline: 32px; } }
        h1, h2 { font-family: var(--font-display); color: var(--fg); line-height: 1.08; letter-spacing: -0.02em; }
        /* Escala padrão do site — a da home, que é a identidade. Página que
           precisar de outra régua sobrescreve no próprio `@push('estilos')`,
           que é o que "Como trabalhamos" faz (texto mais longo pede título
           menor). Antes da extração cada página trazia a sua e não havia
           padrão nenhum: eram duas escalas empatadas. */
        section { padding-block: clamp(72px, 10vw, 128px); scroll-margin-top: 72px; }
        h1 { font-size: clamp(2.6rem, 6vw, 4.5rem); font-weight: 800; }
        h2 { font-size: clamp(1.9rem, 3.5vw, 2.6rem); font-weight: 700; }
        h3 { font-family: var(--font-body); font-weight: 600; font-size: 1.25rem; color: var(--fg); }
        :focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; border-radius: 4px; }

        /* Pular para o conteúdo — invisível até receber foco pelo teclado. */
        .pular { position: absolute; left: 12px; top: -100px; z-index: 100; background: var(--surface); color: var(--fg); border: 1px solid var(--accent); border-radius: var(--r-btn); padding: 12px 18px; text-decoration: none; font-weight: 600; transition: top .18s ease-out; }
        .pular:focus { top: 12px; }

        /* ---------- eyebrow ---------- */
        .eyebrow { display: inline-flex; align-items: center; gap: 10px; font-family: var(--font-mono); font-size: .75rem; letter-spacing: .14em; text-transform: uppercase; color: var(--fg-subtle); }
        .eyebrow::before { content: ""; width: 6px; height: 6px; background: var(--accent); flex: none; }

        /* ---------- botões ---------- */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-weight: 600; font-size: 1rem; padding: 12px 22px; min-height: 44px; border-radius: var(--r-btn); text-decoration: none; border: 1px solid transparent; transition: background .18s ease-out, border-color .18s ease-out, color .18s ease-out; cursor: pointer; font-family: var(--font-body); }
        .btn-primary { background: var(--accent); color: var(--fg-on-accent); }
        .btn-primary:hover { background: var(--accent-hover); }
        .btn-primary:active { background: var(--accent-active); }
        .btn-primary:disabled { opacity: .65; cursor: progress; }
        .btn-ghost { background: transparent; border-color: var(--border); color: var(--fg); }
        .btn-ghost:hover { border-color: var(--accent); }

        /* ---------- header ---------- */
        header { position: sticky; top: 0; z-index: 50; background: var(--header-bg); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border); }
        .nav { display: flex; align-items: center; justify-content: space-between; height: 68px; }
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; min-height: 44px; }
        .brand-mark { width: 34px; height: 34px; border: 1.5px solid var(--accent); border-radius: 8px; display: grid; place-items: center; font-family: var(--font-mono); font-size: .72rem; font-weight: 500; color: var(--accent); }
        .brand-name { font-family: var(--font-display); font-weight: 700; font-size: 1.15rem; color: var(--fg); }
        .brand-name span { color: var(--accent); }
        .nav-links { display: none; align-items: center; gap: 28px; list-style: none; }
        .nav-links a { text-decoration: none; font-size: .95rem; font-weight: 500; color: var(--fg-muted); transition: color .16s ease-out; display: inline-flex; align-items: center; min-height: 44px; }
        .nav-links a:hover { color: var(--fg); }
        .nav-links a[aria-current="page"] { color: var(--accent); }
        .nav-cta { display: none; }
        .nav-toggle { background: none; border: 1px solid var(--border); border-radius: 8px; width: 44px; height: 44px; display: grid; place-items: center; color: var(--fg); cursor: pointer; }
        @media (min-width: 880px) {
            .nav-links, .nav-cta { display: inline-flex; }
            .nav-toggle { display: none; }
        }
        .mobile-menu { display: flex; flex-direction: column; gap: 4px; padding: 0 24px; border-top: 1px solid transparent; max-height: 0; opacity: 0; visibility: hidden; overflow: hidden; transition: max-height .25s ease, opacity .25s ease, padding .25s ease, border-color .25s ease, visibility 0s linear .25s; }
        .mobile-menu.open { max-height: 360px; opacity: 1; visibility: visible; padding: 16px 24px 24px; border-top-color: var(--border); transition: max-height .25s ease, opacity .25s ease, padding .25s ease, border-color .25s ease, visibility 0s; }
        @media (min-width: 880px) { .mobile-menu { display: none; } }
        .mobile-menu a { text-decoration: none; padding: 12px 4px; font-weight: 500; color: var(--fg-muted); display: flex; align-items: center; min-height: 44px; }
        .mobile-menu a:hover { color: var(--fg); }
        .nav-toggle svg { transition: transform .25s ease; }
        .nav-toggle[aria-expanded="true"] svg { transform: rotate(90deg); }

        /* ---------- rodapé ---------- */
        footer { border-top: 1px solid var(--border); padding: 40px 0; background: var(--surface); }
        .foot-grid { display: flex; flex-wrap: wrap; gap: 24px 40px; align-items: center; justify-content: space-between; }
        .foot-grid p { font-size: .9rem; color: var(--fg-subtle); }
        .foot-links { display: flex; flex-wrap: wrap; gap: 8px 24px; list-style: none; }
        .foot-links a { font-size: .95rem; text-decoration: none; color: var(--fg-muted); display: inline-flex; align-items: center; min-height: 44px; }
        .foot-links a:hover { color: var(--fg); }
        .social-bar { display: flex; gap: 12px; }
        .social-bar a { width: 44px; height: 44px; border: 1px solid var(--border); border-radius: 50%; display: grid; place-items: center; color: var(--fg-muted); transition: border-color .18s ease-out, color .18s ease-out, transform .18s ease-out; }
        .social-bar a:hover { transform: translateY(-2px); }

        /* Cores oficiais das marcas — os hex moram na camada de tokens. */
        .s-wa { background: var(--marca-whatsapp) !important; border-color: var(--marca-whatsapp) !important; color: var(--marca-sobre) !important; }
        .s-ig { background: var(--marca-instagram) !important; border-color: transparent !important; color: var(--marca-sobre) !important; }
        .s-li { background: var(--marca-linkedin) !important; border-color: var(--marca-linkedin) !important; color: var(--marca-sobre) !important; }
        .s-mail { background: var(--accent) !important; border-color: var(--accent) !important; color: var(--fg-on-accent) !important; }

        /* ---------- voltar ao topo ---------- */
        .back-to-top { position: fixed; bottom: 24px; right: 24px; z-index: 999; width: 44px; height: 44px; background: var(--surface); color: var(--accent); border: 1px solid var(--border); border-radius: 50%; cursor: pointer; opacity: 0; transform: translateY(10px); transition: opacity .3s ease-out, transform .3s ease-out, border-color .18s ease-out; pointer-events: none; display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-flutuante); }
        .back-to-top.visible { opacity: 1; transform: translateY(0); pointer-events: auto; }
        .back-to-top:hover { border-color: var(--accent); }

        /* ---------- pilha social flutuante (some quando a do rodapé aparece) ---------- */
        .social-float { position: fixed; bottom: 84px; right: 24px; z-index: 999; display: flex; flex-direction: column; gap: 10px; opacity: 0; transform: translateY(10px); transition: opacity .3s ease-out, transform .3s ease-out; pointer-events: none; }
        .social-float.visible { opacity: 1; transform: translateY(0); pointer-events: auto; }
        .social-float a { width: 44px; height: 44px; background: var(--surface); border: 1px solid var(--border); border-radius: 50%; display: grid; place-items: center; color: var(--fg-muted); transition: transform .18s ease-out; box-shadow: var(--shadow-flutuante); }
        .social-float a:hover { transform: translateY(-2px); }

        /* ---------- acessibilidade (FAB + painel) ---------- */
        .a11y { position: fixed; bottom: 24px; left: 24px; z-index: 999; }
        .a11y-fab { width: 44px; height: 44px; background: var(--surface); color: var(--accent); border: 1px solid var(--border); border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: border-color .18s ease-out; box-shadow: var(--shadow-flutuante); }
        .a11y-fab:hover, .a11y-fab[aria-expanded="true"] { border-color: var(--accent); }
        .a11y-panel { position: absolute; bottom: 56px; left: 0; width: min(300px, calc(100vw - 48px)); background: var(--surface-raised); border: 1px solid var(--border); border-radius: var(--r-card); padding: 18px; display: grid; gap: 10px; box-shadow: var(--shadow-painel); }
        .a11y-panel[hidden] { display: none; }
        .a11y-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 4px; }
        .a11y-head strong { font-family: var(--font-display); font-size: 1rem; font-weight: 700; color: var(--fg); }
        .a11y-reset { background: none; border: none; font-family: var(--font-mono); font-size: .7rem; letter-spacing: .1em; text-transform: uppercase; color: var(--fg-subtle); cursor: pointer; padding: 8px 4px; min-height: 44px; }
        .a11y-reset:hover { color: var(--accent-hover); }
        .a11y-label { font-size: .82rem; font-weight: 600; color: var(--fg); }
        .a11y-fonts { display: flex; gap: 8px; }
        .a11y-fonts button { flex: 1; background: var(--bg); border: 1px solid var(--border); border-radius: var(--r-btn); padding: 8px 0; min-height: 44px; color: var(--fg-muted); font-family: var(--font-body); font-weight: 600; cursor: pointer; transition: border-color .16s ease-out, color .16s ease-out; }
        .a11y-fonts button:nth-child(1) { font-size: .85rem; }
        .a11y-fonts button:nth-child(2) { font-size: .98rem; }
        .a11y-fonts button:nth-child(3) { font-size: 1.1rem; }
        .a11y-fonts button[aria-pressed="true"] { border-color: var(--accent); color: var(--accent); }
        .a11y-opt { display: flex; align-items: center; justify-content: space-between; gap: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--r-btn); padding: 10px 12px; min-height: 44px; color: var(--fg-muted); font-family: var(--font-body); font-size: .9rem; cursor: pointer; text-align: left; transition: border-color .16s ease-out, color .16s ease-out; }
        .a11y-opt::after { content: ""; width: 10px; height: 10px; border-radius: 50%; border: 1px solid var(--fg-subtle); flex: none; transition: background .16s ease-out, border-color .16s ease-out; }
        .a11y-opt:hover { border-color: color-mix(in srgb, var(--accent) 40%, var(--border)); }
        .a11y-opt[aria-pressed="true"] { color: var(--fg); border-color: color-mix(in srgb, var(--accent) 45%, var(--border)); }
        .a11y-opt[aria-pressed="true"]::after { background: var(--accent); border-color: var(--accent); }

        /* efeitos das opções (classes no <html>) */
        html.a11y-font-1 { font-size: 112.5%; }
        html.a11y-font-2 { font-size: 125%; }
        html.a11y-underline a { text-decoration: underline !important; }
        html.a11y-spacing :is(p, li, a, span, label, h1, h2, h3, button, input, textarea, strong, b, em) { letter-spacing: .12em !important; word-spacing: .16em !important; line-height: 1.8 !important; }
        html.a11y-pause *, html.a11y-pause *::before, html.a11y-pause *::after { transition-duration: 0s !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; }
        html.a11y-pause .citygrid i.lit::after { animation: none !important; opacity: .35; }

        /* ---------- reveal + reduced motion ---------- */
        /* Scroll de âncora/topo fica suave SEMPRE (regra da casa): reduced-motion
           e "pausar animações" desligam só as animações decorativas, não a
           rolagem de navegação. */
        .reveal { opacity: 0; transform: translateY(12px); transition: opacity .5s ease-out, transform .5s ease-out; }
        .reveal.in { opacity: 1; transform: none; }
        @media (prefers-reduced-motion: reduce) {
            .reveal { opacity: 1; transform: none; transition: none; }
            .citygrid i.lit::after { animation: none; opacity: .35; }
            .mobile-menu, .mobile-menu.open, .nav-toggle svg { transition: none; }
        }
    </style>

    @stack('estilos')
    @stack('head')
</head>
<body>

    <a class="pular" href="#conteudo">Pular para o conteúdo</a>

    <header>
        <div class="container nav">
            <a class="brand" href="{{ $naHome ? '#top' : url('/') }}" aria-label="UrbisDev — início">
                <span class="brand-mark">UD</span>
                <span class="brand-name">urbis<span>Dev</span></span>
            </a>
            <ul class="nav-links">
                <li><a href="{{ $base }}#fazemos">O que fazemos</a></li>
                <li><a href="{{ $base }}#proximos">Próximos passos</a></li>
                <li><a href="{{ url('/como-trabalhamos') }}" @if (! $naHome) aria-current="page" @endif>Como trabalhamos</a></li>
                <li><a href="{{ $base }}#contato">Contato</a></li>
            </ul>
            <a class="btn btn-primary nav-cta" href="{{ $base }}#contato">Fale Conosco</a>
            <button class="nav-toggle" onclick="toggleMenu(this)" aria-label="Abrir menu" aria-expanded="false" aria-controls="mobileMenu">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></svg>
            </button>
        </div>
        <nav class="mobile-menu" id="mobileMenu">
            <a href="{{ $base }}#fazemos" onclick="closeMenu()">O que fazemos</a>
            <a href="{{ $base }}#proximos" onclick="closeMenu()">Próximos passos</a>
            <a href="{{ url('/como-trabalhamos') }}" onclick="closeMenu()">Como trabalhamos</a>
            <a href="{{ $base }}#contato" onclick="closeMenu()">Contato</a>
            <a class="btn btn-primary" style="margin-top:10px" href="{{ $base }}#contato" onclick="closeMenu()">Fale Conosco</a>
        </nav>
    </header>

    <main id="top">
        <span id="conteudo"></span>
        @yield('conteudo')
    </main>

    <footer>
        <div class="container foot-grid">
            <a class="brand" href="{{ $naHome ? '#top' : url('/') }}" aria-label="UrbisDev">
                <span class="brand-mark">UD</span>
                <span class="brand-name">urbis<span>Dev</span></span>
            </a>
            <ul class="foot-links">
                <li><a href="{{ $base }}#fazemos">O que fazemos</a></li>
                <li><a href="{{ $base }}#proximos">Próximos passos</a></li>
                <li><a href="{{ url('/como-trabalhamos') }}">Como trabalhamos</a></li>
                <li><a href="{{ $base }}#contato">Contato</a></li>
            </ul>
            @include('partials.redes', ['classe' => 'social-bar', 'tamanho' => 18, 'rotulo' => null])
            <p>&copy; {{ date('Y') }} UrbisDev. Todos os direitos reservados.</p>
        </div>
    </footer>

    @yield('flutuantes')

    @include('partials.acessibilidade')

    {{-- Pilha social flutuante (espelho da barra do rodapé) --}}
    <div class="social-float" id="socialFloat" aria-label="Redes sociais">
        @include('partials.redes', ['classe' => null, 'tamanho' => 20, 'rotulo' => null])
    </div>

    <button class="back-to-top" id="backToTop" aria-label="Voltar ao topo">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
    </button>

    <script>
        // Menu mobile
        function toggleMenu(btn) {
            var menu = document.getElementById('mobileMenu');
            var aberto = menu.classList.toggle('open');
            btn.setAttribute('aria-expanded', String(aberto));
            btn.setAttribute('aria-label', aberto ? 'Fechar menu' : 'Abrir menu');
        }
        function closeMenu() {
            var menu = document.getElementById('mobileMenu');
            menu.classList.remove('open');
            var btn = document.querySelector('.nav-toggle');
            btn.setAttribute('aria-expanded', 'false');
            btn.setAttribute('aria-label', 'Abrir menu');
        }

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

        // Rolagem suave por JS (rAF): o Chrome desliga o smooth nativo quando o
        // SO pede "reduzir movimento", ignorando até o CSS — a navegação da casa
        // flui sempre, então animamos por conta própria.
        function urbisSmoothTo(targetY) {
            var startY = window.scrollY;
            var dist = targetY - startY;
            if (!dist) return;
            var dur = Math.min(900, Math.max(400, Math.abs(dist) * 0.45));
            var t0 = null;
            function ease(t) { return t < .5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2; }
            function step(ts) {
                if (t0 === null) t0 = ts;
                var p = Math.min(1, (ts - t0) / dur);
                window.scrollTo({ top: startY + dist * ease(p), behavior: 'instant' });
                if (p < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        }
        document.addEventListener('click', function (e) {
            var a = e.target.closest('a[href^="#"]');
            if (!a) return;
            var href = a.getAttribute('href');
            e.preventDefault();
            if (href === '#') return; // link morto (redes ainda sem perfil)
            var el = document.getElementById(href.slice(1));
            if (!el) return;
            urbisSmoothTo(href === '#top' ? 0 : el.getBoundingClientRect().top + window.scrollY - 72);
            history.pushState(null, '', href);
        });

        var backBtn = document.getElementById('backToTop');
        backBtn.addEventListener('click', function () { urbisSmoothTo(0); });
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
    </script>

    @include('partials.progresso-navegacao')

    @stack('scripts')
</body>
</html>
