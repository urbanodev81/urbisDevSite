// Cópia estática da ficha de lançamento: o que levaria a outra página do
// site da imobiliária, ou enviaria um formulário, fica parado e avisa.
(function () {
    var aviso = document.querySelector('.ud-aviso'), relogio;
    function avisar(texto) {
        if (!aviso) return;
        aviso.textContent = texto; aviso.hidden = false;
        clearTimeout(relogio); relogio = setTimeout(function () { aviso.hidden = true; }, 3500);
    }
    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[data-demo-inerte]');
        if (a) { e.preventDefault(); e.stopPropagation(); avisar('Na demonstração este caminho fica parado: no site de verdade ele abre a página da imobiliária.'); }
    }, true);
    document.addEventListener('submit', function (e) {
        e.preventDefault(); e.stopPropagation(); avisar('Demonstração: nada foi enviado. No site de verdade o recado chega à imobiliária.');
    }, true);
})();
