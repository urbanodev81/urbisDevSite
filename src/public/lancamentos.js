// Banner de lançamentos da home. Sem este script os slides aparecem um embaixo
// do outro, e nada se perde. A troca automática para no mouse, no foco e no botão
// de pausa, e nem começa para quem pediu menos movimento.
(function () {
    var banner = document.getElementById('lancamentos');
    if (!banner) return;

    var slides = Array.prototype.slice.call(banner.querySelectorAll('.lanc-slide'));
    if (slides.length < 2) return;

    var INTERVALO = 8000;
    var pontos = Array.prototype.slice.call(banner.querySelectorAll('.lanc-ponto'));
    var pausa = banner.querySelector('.lanc-pausa');
    var trilho = banner.querySelector('.lanc-trilho');
    var atual = 0;
    var timer = null;
    var pausado = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var dentro = false;

    function mostrar(i) {
        atual = (i + slides.length) % slides.length;
        slides.forEach(function (slide, n) {
            var ativo = n === atual;
            slide.classList.toggle('ativo', ativo);
            slide.inert = !ativo;
        });
        pontos.forEach(function (ponto, n) {
            if (n === atual) ponto.setAttribute('aria-current', 'true');
            else ponto.removeAttribute('aria-current');
        });
    }

    function acertar() {
        clearInterval(timer);
        timer = null;
        // Leitor de tela só é avisado da troca que a pessoa pediu.
        trilho.setAttribute('aria-live', pausado || dentro ? 'polite' : 'off');
        if (!pausado && !dentro) {
            timer = setInterval(function () { mostrar(atual + 1); }, INTERVALO);
        }
    }

    function desenharPausa() {
        pausa.setAttribute('aria-label', pausado ? 'Retomar a passagem dos lançamentos' : 'Pausar a passagem dos lançamentos');
        pausa.querySelector('.lanc-icone-pausa').toggleAttribute('hidden', pausado);
        pausa.querySelector('.lanc-icone-tocar').toggleAttribute('hidden', !pausado);
    }

    banner.querySelector('.lanc-anterior').addEventListener('click', function () { mostrar(atual - 1); });
    banner.querySelector('.lanc-proximo').addEventListener('click', function () { mostrar(atual + 1); });
    pontos.forEach(function (ponto, n) {
        ponto.addEventListener('click', function () { mostrar(n); });
    });
    pausa.addEventListener('click', function () {
        pausado = !pausado;
        desenharPausa();
        acertar();
    });

    banner.addEventListener('mouseenter', function () { dentro = true; acertar(); });
    banner.addEventListener('mouseleave', function () { dentro = banner.contains(document.activeElement); acertar(); });
    banner.addEventListener('focusin', function () { dentro = true; acertar(); });
    banner.addEventListener('focusout', function (e) {
        if (!banner.contains(e.relatedTarget)) { dentro = banner.matches(':hover'); acertar(); }
    });

    banner.classList.add('em-passagem');
    mostrar(0);
    desenharPausa();
    acertar();
})();
