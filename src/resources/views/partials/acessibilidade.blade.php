{{--
    Widget de acessibilidade — FAB + painel (tamanho de texto, sublinhar links,
    espaçamento, pausar animações), com persistência em localStorage.

    CÓPIA do bloco que vive em welcome.blade.php. A duplicação é consciente e
    temporária: recortar da homepage em produção sem o Ricardo ver era risco
    desnecessário. A resolução é extrair o casco compartilhado (head, header,
    rodapé, este widget) para um layout Blade e fazer as duas páginas
    estenderem — está registrado como próximo passo.
--}}

<style>
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
</style>

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

<script>
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
</script>
