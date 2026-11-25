/*
 * Topo da home com movimento (24/11/2026). JavaScript puro, sem biblioteca,
 * sem build. A mesma linguagem da vitrine do urbanodev.com.br
 * (sistemas/urbanodev-com-br/web/vitrine.js), adaptada a este topo:
 *
 *  1. Palavras: ações reais dos nossos projetos vagueiam por cima da grade de
 *     janelas, fogem do cursor e andam em camadas (paralaxe). A cada 1,6 s uma
 *     "acende" e o selo "UD" do cabeçalho pulsa junto. Elas evitam o retângulo
 *     REAL do texto do topo (que aqui fica à esquerda, não no centro).
 *  2. Logo: o selo "UD" se desenha e o nome sobe letra a letra; o título entra
 *     palavra a palavra, preservando o <em> que vem do painel.
 *  3. Layouts ao fundo: duas fileiras de prints (clientes novos, modelos do USI
 *     e fichas de lançamento) passando devagar. Carregam DEPOIS do `load`, para
 *     não pesar na primeira pintura. Só em tela larga (>= 880 px).
 *
 * "Reduzir movimento" ligado: nada se mexe, os prints ficam parados.
 * O laço de quadro para quando o topo sai da tela ou a aba fica escondida.
 */
(function () {
    'use strict';
    var hero = document.querySelector('.hero');
    if (!hero) { return; }
    var parado = matchMedia('(prefers-reduced-motion: reduce)').matches;
    var mouseFino = matchMedia('(pointer: fine)').matches;
    var texto = hero.querySelector('.hero-inner');
    var selo = document.querySelector('header .brand-mark');
    function lerp(a, b, t) { return a + (b - a) * t; }

    document.documentElement.classList.add('topo-vivo');

    /* ---------- 2. logo e título ---------- */
    var marca = document.querySelector('header .brand');
    if (marca && selo) {
        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('class', 'selo-traco');
        svg.setAttribute('viewBox', '0 0 34 34');
        svg.setAttribute('aria-hidden', 'true');
        svg.innerHTML = '<rect x=".75" y=".75" width="32.5" height="32.5" rx="7.25" pathLength="1"/>';
        selo.appendChild(svg);
        letraALetra(marca.querySelector('.brand-name'), 'letra', 45, 350);
        marca.classList.add('logo-entra');
    }
    var titulo = hero.querySelector('h1');
    if (titulo) { palavraAPalavra(titulo); }

    /* Quebra o texto de um elemento em <span>, um por letra, sem perder os
       filhos (o "Dev" do logo é um <span> com cor própria). */
    function letraALetra(el, classe, passo, atraso) {
        if (!el) { return; }
        var k = 0;
        [].slice.call(el.childNodes).forEach(function (no) {
            var frag = document.createDocumentFragment();
            no.textContent.split('').forEach(function (ch) {
                var s = document.createElement('span');
                s.className = classe;
                s.style.setProperty('--d', (atraso + k++ * passo) + 'ms');
                s.textContent = ch;
                frag.appendChild(s);
            });
            if (no.nodeType === 3) { el.replaceChild(frag, no); } else { no.textContent = ''; no.appendChild(frag); }
        });
    }
    /* Título palavra a palavra: o leitor de tela continua lendo a frase
       inteira, porque os <span> são inline e os espaços ficam entre eles. */
    function palavraAPalavra(el) {
        var k = 0;
        (function anda(pai) {
            [].slice.call(pai.childNodes).forEach(function (no) {
                if (no.nodeType === 1) { anda(no); return; }
                if (no.nodeType !== 3 || !no.textContent.trim()) { return; }
                var frag = document.createDocumentFragment();
                no.textContent.split(/(\s+)/).forEach(function (pedaco) {
                    if (!pedaco) { return; }
                    if (/^\s+$/.test(pedaco)) { frag.appendChild(document.createTextNode(pedaco)); return; }
                    var s = document.createElement('span');
                    s.className = 'termo';
                    s.style.setProperty('--d', (150 + k++ * 70) + 'ms');
                    s.textContent = pedaco;
                    frag.appendChild(s);
                });
                pai.replaceChild(frag, no);
            });
        })(el);
    }

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
    hero.insertBefore(nuvem, texto);
    var corpos = [];

    function espalhar() {
        nuvem.textContent = '';
        corpos = [];
        var hr = hero.getBoundingClientRect();
        var w = hr.width, h = hr.height;
        /* o retângulo do texto, com folga: é dali que as palavras se afastam */
        var tr = texto.getBoundingClientRect();
        var livre = [tr.left - hr.left - 24, tr.top - hr.top - 16, tr.width + 48, tr.height + 32];
        var postas = [livre];
        var lista = w < 700 ? PALAVRAS.filter(function (_, i) { return i % 2 === 0; }) : PALAVRAS;
        lista.forEach(function (p, i) {
            var prof = .35 + Math.random() * .65;
            var el = document.createElement('span');
            el.className = 'palavra' + (p[1] ? ' quente' : '');
            el.style.setProperty('--prof', prof.toFixed(2));
            el.style.setProperty('--surge', (500 + i * 70) + 'ms');
            el.textContent = p[0];
            nuvem.appendChild(el);
            var ew = el.offsetWidth, eh = el.offsetHeight, x, y, ok = false;
            for (var t = 0; t < 80 && !ok; t++) {
                /* margem de 56 px: a deriva e a paralaxe somam até ~50 px */
                x = Math.random() * (w - ew - 112) + 56;
                y = Math.random() * (h - eh - 64) + 32;
                ok = postas.every(function (r) {
                    return x + ew + 40 < r[0] || x > r[0] + r[2] + 40 || y + eh + 26 < r[1] || y > r[1] + r[3] + 26;
                });
            }
            if (!ok) { el.remove(); return; }
            postas.push([x, y, ew, eh]);
            el.style.left = x + 'px';
            el.style.top = y + 'px';
            corpos.push({
                el: el, bx: x + ew / 2, by: y + eh / 2, prof: prof,
                fx: .00018 + Math.random() * .00022, fy: .00015 + Math.random() * .0002,
                ax: 8 + Math.random() * 18, ay: 6 + Math.random() * 12,
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

    var cursor = { x: -9999, y: -9999, dentro: false, sx: .5, sy: .5, nx: .5, ny: .5 };
    if (mouseFino) {
        hero.addEventListener('mousemove', function (e) {
            var r = hero.getBoundingClientRect();
            cursor.x = e.clientX - r.left; cursor.y = e.clientY - r.top; cursor.dentro = true;
            cursor.nx = cursor.x / r.width; cursor.ny = cursor.y / r.height;
        });
        hero.addEventListener('mouseleave', function () { cursor.dentro = false; cursor.x = cursor.y = -9999; cursor.nx = cursor.ny = .5; });
    }

    var visivel = true, quadro = 0;
    function passo(t) {
        quadro = 0;
        if (!visivel || document.hidden) { return; }
        cursor.sx = lerp(cursor.sx, cursor.nx, .06);
        cursor.sy = lerp(cursor.sy, cursor.ny, .06);
        hero.style.setProperty('--gx', (cursor.sx * 100).toFixed(2) + '%');
        hero.style.setProperty('--gy', (cursor.sy * 100).toFixed(2) + '%');
        var mx = cursor.sx - .5, my = cursor.sy - .5;
        for (var i = 0; i < corpos.length; i++) {
            var c = corpos[i];
            /* vaguear: duas senoides fora de fase, um caminho que não se repete à vista */
            var vx = Math.sin(t * c.fx + c.fase) * c.ax + Math.sin(t * c.fx * 2.3 + c.fase * 1.7) * c.ax * .35;
            var vy = Math.cos(t * c.fy + c.fase) * c.ay + Math.sin(t * c.fy * 1.9 + c.fase * .6) * c.ay * .35;
            /* fugir do cursor: empurrão que cresce perto dele */
            var ex = 0, ey = 0;
            if (cursor.dentro) {
                var dx = c.bx + c.px - cursor.x, dy = c.by + c.py - cursor.y;
                var d = Math.sqrt(dx * dx + dy * dy) || 1, R = 170;
                if (d < R) { var f = Math.pow(1 - d / R, 2) * 80 * (.5 + c.prof); ex = dx / d * f; ey = dy / d * f; }
            }
            c.px = lerp(c.px, ex, .08); c.py = lerp(c.py, ey, .08);
            var x = vx + c.px - mx * 60 * c.prof, y = vy + c.py - my * 40 * c.prof;
            c.el.style.transform = 'translate3d(' + x.toFixed(1) + 'px,' + y.toFixed(1) + 'px,0)';
        }
        quadro = requestAnimationFrame(passo);
    }
    function liga() { if (!quadro && !parado) { quadro = requestAnimationFrame(passo); } }

    /* ---------- 3. layouts ao fundo ---------- */
    var PRINTS = [
        'usi-prestige', 'cliente-jacob', 'ficha-modern', 'usi-panorama', 'cliente-wj', 'ficha-gallery',
        'usi-signature', 'usi-alameda', 'cliente-taveira', 'ficha-classic', 'usi-costa',
        'usi-metropole', 'ficha-brochure', 'usi-radar', 'ficha-minimal'
    ];
    function montaFundo() {
        /* celular: o texto ocupa a largura toda e os prints atrapalhariam a
           leitura. Nem baixa as imagens. */
        if (innerWidth < 880) { return; }
        var fundo = document.createElement('div');
        fundo.className = 'layouts';
        fundo.setAttribute('aria-hidden', 'true');
        var meio = Math.ceil(PRINTS.length / 2);
        [PRINTS.slice(0, meio), PRINTS.slice(meio)].forEach(function (fila, n) {
            var trilho = document.createElement('div');
            trilho.className = 'layouts-trilho' + (n ? ' volta' : '');
            /* a fila vai duas vezes: quando a primeira sai, a segunda está no lugar dela */
            fila.concat(fila).forEach(function (nome) {
                var img = new Image(240, 135);
                img.alt = '';
                img.decoding = 'async';
                img.src = '/img/vitrine/' + nome + '.webp';
                trilho.appendChild(img);
            });
            fundo.appendChild(trilho);
        });
        hero.insertBefore(fundo, hero.firstChild);
        requestAnimationFrame(function () { fundo.classList.add('pronto'); });
    }
    if (document.readyState === 'complete') { montaFundo(); }
    else { addEventListener('load', montaFundo); }

    if (!parado) {
        new IntersectionObserver(function (e) {
            visivel = e[0].isIntersecting;
            hero.classList.toggle('fora', !visivel);
            if (visivel) { liga(); }
        }).observe(hero);
        document.addEventListener('visibilitychange', liga);
        liga();

        /* uma ação "acende", e o selo do cabeçalho responde */
        setInterval(function () {
            if (!visivel || document.hidden || !corpos.length) { return; }
            var c = corpos[Math.floor(Math.random() * corpos.length)];
            c.el.classList.remove('viva');
            void c.el.offsetWidth;
            c.el.classList.add('viva');
            if (selo && scrollY < 40) {
                selo.classList.remove('pulsa');
                void selo.offsetWidth;
                selo.classList.add('pulsa');
            }
        }, 1600);
    }
})();
