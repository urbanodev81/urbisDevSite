{{--
    Indicador de navegação entre páginas.

    O site é Blade multi-página: cada clique no menu troca o documento inteiro.
    Entre o clique e a nova tela o navegador não devolve nada além do spinner
    da aba — em conexão ruim isso lê como "cliquei e não aconteceu nada".

    É barra no topo, e não modal, de propósito: overlay bloqueante cobre
    conteúdo que ainda está legível, prende o foco e pisca quando a página
    chega rápido. A barra só nasce depois de 180 ms de espera, justamente
    para não piscar em navegação instantânea.

    Incluído por welcome.blade.php e como-trabalhamos.blade.php.
--}}
<div class="nav-progress" id="navProgress" aria-hidden="true"><span></span></div>

<style>
    .nav-progress { position: fixed; top: 0; left: 0; right: 0; height: 3px; z-index: 1000; pointer-events: none; opacity: 0; transition: opacity .2s ease-out; }
    .nav-progress.on { opacity: 1; }
    .nav-progress span { display: block; height: 100%; width: 0; background: linear-gradient(90deg, var(--amber-600), var(--amber-400)); box-shadow: 0 0 8px var(--amber-600); transition: width .25s ease-out; }

    @media (prefers-reduced-motion: reduce) {
        .nav-progress, .nav-progress span { transition: none; }
    }
</style>

<script>
(function () {
    var barra = document.getElementById('navProgress');
    if (!barra) return;

    var trilho = barra.firstElementChild;
    var carencia = null;
    var avanco = null;

    function comecar() {
        if (carencia || avanco) return;

        // Carência: navegação que resolve em milissegundos não pisca barra.
        carencia = setTimeout(function () {
            carencia = null;
            var pct = 8;
            barra.classList.add('on');
            trilho.style.width = pct + '%';

            // Nunca chega a 100% sozinha — quem completa é a página nova.
            avanco = setInterval(function () {
                pct += (90 - pct) * 0.12;
                trilho.style.width = pct + '%';
            }, 220);
        }, 180);
    }

    function parar() {
        clearTimeout(carencia);
        clearInterval(avanco);
        carencia = avanco = null;
        barra.classList.remove('on');
        trilho.style.width = '0';
    }

    document.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.button !== 0) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        if (!e.target || !e.target.closest) return;

        var link = e.target.closest('a[href]');
        if (!link || link.target === '_blank' || link.hasAttribute('download')) return;

        var destino = new URL(link.getAttribute('href'), location.href);
        if (destino.origin !== location.origin) return;

        // Âncora dentro da mesma página não troca documento.
        if (destino.pathname === location.pathname && destino.search === location.search) return;

        comecar();
    });

    // Voltar pelo histórico devolve a página pronta (bfcache): apaga a barra.
    window.addEventListener('pageshow', parar);
})();
</script>
