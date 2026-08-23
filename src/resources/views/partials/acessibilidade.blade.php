{{--
    Widget de acessibilidade — FAB + painel: tema, tamanho de texto, sublinhar
    links, espaçamento, pausar animações. Tudo persistido em localStorage.

    Até 23/08/2026 este arquivo era uma CÓPIA declarada do bloco que vivia
    dentro de `welcome.blade.php` — o próprio comentário do topo dizia isso e
    apontava a resolução: extrair o casco para um layout. Feito: o CSS foi
    para `layouts/site.blade.php` (junto com o resto do cromo) e este arquivo
    ficou só com marcação e comportamento, incluído uma vez pelo layout.

    O SELETOR DE TEMA MORA AQUI DE PROPÓSITO

    O CORE §2 exige que o seletor seja visível na interface, não escondido em
    configuração — e o lugar onde a pessoa já vem procurar "como eu leio isto
    melhor" é este painel. Ele grava a MESMA chave que o script síncrono do
    `<head>` lê, e não reimplementa a regra de resolução: quem está em
    "Sistema" apaga a chave e deixa o `prefers-color-scheme` decidir. Duas
    fontes de verdade para o tema é o defeito que o contrato descreve.
--}}

<div class="a11y" id="a11yWidget">
    <div class="a11y-panel" id="a11yPanel" role="dialog" aria-label="Opções de acessibilidade" hidden>
        <div class="a11y-head">
            <strong>Acessibilidade</strong>
            <button type="button" class="a11y-reset" id="a11yReset">Redefinir</button>
        </div>

        <span class="a11y-label" id="a11yTemaLabel">Tema</span>
        <div class="a11y-fonts" role="group" aria-labelledby="a11yTemaLabel">
            <button type="button" data-a11y-tema="escuro" aria-pressed="false">Escuro</button>
            <button type="button" data-a11y-tema="claro" aria-pressed="false">Claro</button>
            <button type="button" data-a11y-tema="sistema" aria-pressed="true">Sistema</button>
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
    (function () {
        var KEY = 'urbis-a11y';
        var KEY_TEMA = 'urbis-tema';   // a MESMA chave do script do <head>
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

        // ---- tema -----------------------------------------------------------
        // 'sistema' NÃO é um valor gravado: é a ausência de valor. Guardar a
        // string 'sistema' obrigaria o script do <head> a conhecê-la também, e
        // aí seriam duas regras de resolução para a mesma pergunta.
        function temaSalvo() {
            try { return localStorage.getItem(KEY_TEMA); } catch (e) { return null; }
        }

        function aplicarTema() {
            var t = temaSalvo();
            var claro = t ? t === 'claro'
                          : window.matchMedia('(prefers-color-scheme: light)').matches;
            root.classList.toggle('claro', claro);
            panel.querySelectorAll('[data-a11y-tema]').forEach(function (b) {
                var v = b.getAttribute('data-a11y-tema');
                b.setAttribute('aria-pressed', String(t ? v === t : v === 'sistema'));
            });
        }

        panel.querySelectorAll('[data-a11y-tema]').forEach(function (b) {
            b.addEventListener('click', function () {
                var v = b.getAttribute('data-a11y-tema');
                try {
                    if (v === 'sistema') { localStorage.removeItem(KEY_TEMA); }
                    else { localStorage.setItem(KEY_TEMA, v); }
                } catch (e) { /* sem persistência: vale só nesta página */ }
                if (v !== 'sistema') { root.classList.toggle('claro', v === 'claro'); }
                aplicarTema();
            });
        });

        // Quem está em "Sistema" acompanha a troca do SO sem recarregar.
        try {
            window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', function () {
                if (!temaSalvo()) { aplicarTema(); }
            });
        } catch (e) { /* navegador antigo: só não acompanha em tempo real */ }

        // ---- demais opções ---------------------------------------------------
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
            try { localStorage.removeItem(KEY_TEMA); } catch (e) { /* noop */ }
            apply();
            aplicarTema();
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
        aplicarTema();
    })();
</script>
