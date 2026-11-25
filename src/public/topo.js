/*
 * Palco do topo da home (25/11/2026). JavaScript puro, sem biblioteca, sem
 * build. É a parte 1 do `vitrine.js` do urbanodev.com.br
 * (sistemas/urbanodev-com-br/web, commit 264d073), adaptada a este site:
 *
 *  1. Palavras: ações reais dos nossos projetos vagueiam em volta do logo,
 *     fogem do cursor e andam em camadas (paralaxe). A cada 1,1 s uma "acende"
 *     e o selo "UD" do palco brilha junto. O brilho âmbar segue o cursor e o
 *     botão "Fale conosco" é magnético.
 *  2. Layouts ao fundo: duas fileiras de prints (clientes novos, modelos do USI
 *     e fichas de lançamento) passando em sentidos opostos. Carregam DEPOIS do
 *     `load`, para não pesar na primeira pintura.
 *
 * O logo (selo que se desenha, letras em cascata) é CSS puro sobre os <span>
 * que o Blade já escreve: não depende deste arquivo.
 *
 * Modo calmo = opção "Reduzir movimento" do widget de acessibilidade (classe
 * `a11y-calmo` no <html>; "Pausar animações", `a11y-pause`, também conta).
 * Não é o `prefers-reduced-motion` do sistema que decide (25/11/2026): com as
 * animações do Windows desligadas o Chrome pede "reduzir" e o topo ficava
 * calmo para quem nem sabia da opção. No modo calmo nada se desloca, mas as
 * palavras acendem, o selo brilha e os prints trocam no lugar, esmaecendo.
 * Troca ao vivo: um MutationObserver no <html> percebe o clique no widget.
 * O laço de quadro para quando o palco sai da tela ou a aba fica escondida.
 */
(function () {
    'use strict';
    var palco = document.getElementById('palco');
    if (!palco) { return; }
    var raiz = document.documentElement;
    function querCalma() { return raiz.classList.contains('a11y-calmo') || raiz.classList.contains('a11y-pause'); }
    var parado = querCalma();
    var mouseFino = matchMedia('(pointer: fine)').matches;
    var botao = palco.querySelector('.btn-primary');
    var selo = palco.querySelector('.palco-selo');
    function lerp(a, b, t) { return a + (b - a) * t; }

    /* ---------- 1. palavras ---------- */
    /* Cada uma é uma ação de um projeto nosso (USI, SSB, Synapse, Movana,
       BootHelpDesk, sites de banda e de oficina). 1 = cor da marca.
       A MESMA lista do urbanodev.com.br: mudou lá, muda aqui. */
    var PALAVRAS = [
        ['anunciar imóvel', 1], ['agendar visita', 0], ['captar leads', 1], ['publicar nos portais', 0],
        ['emitir boleto', 0], ['receber por PIX', 1], ['montar o site', 0], ['votar em assembleia', 0],
        ['filiar associado', 0], ['registrar sessão', 0], ['acompanhar evolução', 1], ['marcar treino', 0],
        ['abrir chamado', 0], ['divulgar a agenda', 0], ['vender show', 1], ['pedir orçamento', 0],
        ['publicar fotos', 0], ['app no celular', 1], ['avisar no WhatsApp', 0], ['tema escuro', 0],
        ['acessível', 0], ['relatório do mês', 0]
    ];
    var nuvem = document.createElement('div');
    nuvem.className = 'nuvem';
    nuvem.setAttribute('aria-hidden', 'true');
    palco.insertBefore(nuvem, palco.firstChild);
    var corpos = [];

    function espalhar() {
        nuvem.textContent = '';
        corpos = [];
        var w = palco.clientWidth, h = palco.clientHeight;
        var estreito = w < 700;
        var lista = estreito ? PALAVRAS.filter(function (_, i) { return i % 2 === 0; }) : PALAVRAS;
        /* o miolo livre, em fração do palco: é onde moram logo, lema e botões */
        var livreX = estreito ? .46 : .34, livreY = estreito ? .24 : .26;
        var postas = [];
        lista.forEach(function (p, i) {
            var prof = .35 + Math.random() * .65;
            var el = document.createElement('span');
            el.className = 'palavra' + (p[1] ? ' quente' : '');
            el.style.setProperty('--prof', prof.toFixed(2));
            el.style.setProperty('--surge', (400 + i * 70) + 'ms');
            el.textContent = p[0];
            nuvem.appendChild(el);
            var ew = el.offsetWidth, eh = el.offsetHeight, x, y, ok = false;
            for (var t = 0; t < 80 && !ok; t++) {
                /* margem de 80 px: a deriva e a paralaxe somam até ~110 px */
                x = Math.random() * (w - ew - 160) + 80;
                y = Math.random() * (h - eh - 160) + 80;
                var cx = (x + ew / 2) / w - .5, cy = (y + eh / 2) / h - .5;
                ok = (Math.abs(cx) > livreX || Math.abs(cy) > livreY) && postas.every(function (r) {
                    return x + ew + 24 < r[0] || x > r[0] + r[2] + 24 || y + eh + 14 < r[1] || y > r[1] + r[3] + 14;
                });
            }
            if (!ok) { el.remove(); return; }
            postas.push([x, y, ew, eh]);
            el.style.left = x + 'px';
            el.style.top = y + 'px';
            corpos.push({
                el: el, bx: x + ew / 2, by: y + eh / 2, prof: prof,
                fx: .00035 + Math.random() * .0004, fy: .0003 + Math.random() * .00035,
                ax: 28 + Math.random() * 46, ay: 18 + Math.random() * 30,
                giro: 3 + Math.random() * 5,
                fase: Math.random() * 6.28, px: 0, py: 0
            });
        });
    }
    espalhar();
    var adiar, larguraAntes = innerWidth;
    /* No celular a barra de endereço some e o resize dispara com a mesma
       largura: só re-espalha quando a largura muda de verdade. */
    addEventListener('resize', function () {
        if (innerWidth === larguraAntes) { return; }
        larguraAntes = innerWidth;
        clearTimeout(adiar); adiar = setTimeout(espalhar, 200);
    });

    /* estado do cursor dentro do palco (posição real e suavizada) */
    var cursor = { x: -9999, y: -9999, dentro: false, sx: .5, sy: .5, nx: .5, ny: .5 };
    if (mouseFino) {
        palco.addEventListener('mousemove', function (e) {
            var r = palco.getBoundingClientRect();
            cursor.x = e.clientX - r.left; cursor.y = e.clientY - r.top; cursor.dentro = true;
            cursor.nx = cursor.x / r.width; cursor.ny = cursor.y / r.height;
        });
        palco.addEventListener('mouseleave', function () { cursor.dentro = false; cursor.x = cursor.y = -9999; cursor.nx = cursor.ny = .5; });
    }

    var visivel = true, quadro = 0;
    function passo(t) {
        quadro = 0;
        if (!visivel || document.hidden || parado) { return; }
        cursor.sx = lerp(cursor.sx, cursor.nx, .06);
        cursor.sy = lerp(cursor.sy, cursor.ny, .06);
        palco.style.setProperty('--gx', (cursor.sx * 100).toFixed(2) + '%');
        palco.style.setProperty('--gy', (cursor.sy * 100).toFixed(2) + '%');
        var mx = cursor.sx - .5, my = cursor.sy - .5;
        for (var i = 0; i < corpos.length; i++) {
            var c = corpos[i];
            /* vaguear: duas senoides fora de fase dão um caminho que não se repete à vista */
            var vx = Math.sin(t * c.fx + c.fase) * c.ax + Math.sin(t * c.fx * 2.3 + c.fase * 1.7) * c.ax * .35;
            var vy = Math.cos(t * c.fy + c.fase) * c.ay + Math.sin(t * c.fy * 1.9 + c.fase * .6) * c.ay * .35;
            /* fugir do cursor: empurrão que cresce perto dele, mais forte nas palavras de perto */
            var ex = 0, ey = 0;
            if (cursor.dentro) {
                var dx = c.bx + c.px - cursor.x, dy = c.by + c.py - cursor.y;
                var d = Math.sqrt(dx * dx + dy * dy) || 1, R = 240;
                if (d < R) { var f = Math.pow(1 - d / R, 2) * 150 * (.5 + c.prof); ex = dx / d * f; ey = dy / d * f; }
            }
            c.px = lerp(c.px, ex, .08); c.py = lerp(c.py, ey, .08);
            /* paralaxe: as de perto andam mais */
            var x = vx + c.px - mx * 140 * c.prof, y = vy + c.py - my * 96 * c.prof;
            /* inclina para o lado em que está indo: dá sensação de deriva */
            var giro = Math.sin(t * c.fx * 1.4 + c.fase) * c.giro;
            c.el.style.transform = 'translate3d(' + x.toFixed(1) + 'px,' + y.toFixed(1) + 'px,0) rotate(' + giro.toFixed(2) + 'deg)';
        }
        /* botão magnético: só o primário, o "Veja funcionando" fica quieto */
        if (botao && cursor.dentro) {
            var r = botao.getBoundingClientRect(), pr = palco.getBoundingClientRect();
            var bx = r.left - pr.left + r.width / 2, by = r.top - pr.top + r.height / 2;
            var ddx = cursor.x - bx, ddy = cursor.y - by, dd = Math.sqrt(ddx * ddx + ddy * ddy);
            var puxa = dd < 110 ? (1 - dd / 110) : 0;
            botao.style.transform = 'translate(' + (ddx * puxa * .22).toFixed(1) + 'px,' + (ddy * puxa * .22).toFixed(1) + 'px)';
        } else if (botao) { botao.style.transform = ''; }
        quadro = requestAnimationFrame(passo);
    }
    function liga() { if (!quadro && !parado) { quadro = requestAnimationFrame(passo); } }

    /* ---------- 2. layouts ao fundo ----------
       Só print de layout NOVO e aberto ao público (ver o CLAUDE.md do repo). */
    var PRINTS = [
        'usi-prestige', 'cliente-jacob', 'ficha-modern', 'usi-panorama', 'cliente-wj', 'ficha-gallery',
        'usi-signature', 'usi-alameda', 'cliente-taveira', 'ficha-classic', 'usi-costa',
        'usi-metropole', 'ficha-brochure', 'usi-radar', 'ficha-minimal'
    ];
    var fundo = null, trocaCalma = 0, carregou = document.readyState === 'complete';
    function imagem(nome) {
        var img = new Image(320, 180);
        img.alt = '';
        img.decoding = 'async';
        img.src = '/img/vitrine/' + nome + '.webp';
        return img;
    }
    function montaFundo() {
        if (fundo) { fundo.remove(); }
        clearInterval(trocaCalma);
        fundo = document.createElement('div');
        fundo.setAttribute('aria-hidden', 'true');
        fundo.className = 'layouts' + (parado ? ' calmo' : '');
        var vagas = [];
        var meio = Math.ceil(PRINTS.length / 2);
        [PRINTS.slice(0, meio), PRINTS.slice(meio)].forEach(function (fila, n) {
            var trilho = document.createElement('div');
            trilho.className = 'layouts-trilho' + (n ? ' volta' : '');
            /* com movimento a fila vai duas vezes: quando a primeira sai, a segunda está no lugar */
            (parado ? fila : fila.concat(fila)).forEach(function (nome) {
                var img = imagem(nome);
                trilho.appendChild(img);
                vagas.push(img);
            });
            fundo.appendChild(trilho);
        });
        palco.insertBefore(fundo, palco.firstChild);
        requestAnimationFrame(function () { fundo.classList.add('pronto'); });
        if (!parado) { return; }
        /* modo calmo: troca uma imagem por vez, esmaecendo, sem mover nada */
        var proxima = 0;
        trocaCalma = setInterval(function () {
            if (!visivel || document.hidden) { return; }
            var img = vagas[Math.floor(Math.random() * vagas.length)];
            img.classList.add('trocando');
            setTimeout(function () {
                var atual = img.src.split('/').pop().replace('.webp', '');
                var nome = PRINTS[proxima++ % PRINTS.length];
                if (nome === atual) { nome = PRINTS[proxima++ % PRINTS.length]; }
                img.src = '/img/vitrine/' + nome + '.webp';
                img.classList.remove('trocando');
            }, 900);
        }, 2600);
    }
    if (carregou) { montaFundo(); }
    else { addEventListener('load', function () { carregou = true; montaFundo(); }); }

    /* o widget de acessibilidade troca a classe no <html>: troca de modo na hora */
    new MutationObserver(function () {
        var agora = querCalma();
        if (agora === parado) { return; }
        parado = agora;
        if (parado) {
            corpos.forEach(function (c) { c.el.style.transform = ''; c.px = c.py = 0; });
            if (botao) { botao.style.transform = ''; }
        } else { liga(); }
        if (carregou) { montaFundo(); }
    }).observe(raiz, { attributes: true, attributeFilter: ['class'] });

    /* Observador e "acender" valem também no modo calmo: cor e brilho não são
       deslocamento. O laço de quadro (liga) é que não roda com `parado`. */
    new IntersectionObserver(function (e) {
        visivel = e[0].isIntersecting;
        palco.classList.toggle('fora', !visivel);
        if (visivel) { liga(); }
    }).observe(palco);
    document.addEventListener('visibilitychange', liga);
    liga();

    /* uma ação "acende", e o selo do palco brilha junto */
    setInterval(function () {
        if (!visivel || document.hidden || !corpos.length) { return; }
        var c = corpos[Math.floor(Math.random() * corpos.length)];
        c.el.classList.remove('viva');
        void c.el.offsetWidth;
        c.el.classList.add('viva');
        if (selo && Math.random() < .6) {
            selo.classList.remove('pulsa');
            void selo.getBoundingClientRect();
            selo.classList.add('pulsa');
        }
    }, 1100);
})();
