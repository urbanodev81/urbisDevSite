/*
 * Gráficos das demonstrações do site — SVG puro, sem biblioteca.
 *
 * Por que à mão: o site não tem build nem dependência de front, e continua
 * assim. São quatro formas (linha, barras, colunas, mapa de calor), todas com
 * as mesmas regras:
 *
 * - Cor vem de TOKEN (`--serie-1..4`), nunca de hex aqui: os dois temas trocam
 *   sozinhos. A paleta foi validada para daltonismo nos dois fundos.
 * - Texto nunca veste a cor da série: rótulo, valor e legenda usam os tokens
 *   de texto, e a identidade vem da marca colorida ao lado.
 * - Todo gráfico tem dica ao passar o mouse (ou tocar) e uma TABELA com os
 *   mesmos números, que é o que o leitor de tela lê.
 * - Nenhum dado de fora vira HTML: tudo entra por `textContent`.
 */
(function () {
    'use strict';

    const NS = 'http://www.w3.org/2000/svg';

    function svg(tag, attrs, pai) {
        const e = document.createElementNS(NS, tag);
        for (const k in attrs || {}) e.setAttribute(k, attrs[k]);
        if (pai) pai.appendChild(e);
        return e;
    }

    function html(tag, classe, texto, pai) {
        const e = document.createElement(tag);
        if (classe) e.className = classe;
        if (texto !== undefined && texto !== null) e.textContent = texto;
        if (pai) pai.appendChild(e);
        return e;
    }

    const fmt = {
        int: (n) => Math.round(n).toLocaleString('pt-BR'),
        dec: (n) => n.toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }),
        pct: (n) => Math.round(n) + '%',
        brl: (n) => 'R$ ' + Math.round(n).toLocaleString('pt-BR'),
        // Eixo: compacto de mil para cima (0 / 10 mil / 20 mil).
        compacto: (n) => (Math.abs(n) >= 1000 ? (n / 1000).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + ' mil' : Math.round(n).toLocaleString('pt-BR')),
        min: (n) => (n >= 60 ? Math.floor(n / 60) + ' h ' + String(Math.round(n % 60)).padStart(2, '0') : Math.round(n) + ' min'),
    };

    /* Sorteio com semente: a demonstração mostra os MESMOS números a todo visitante. */
    function sorteio(semente) {
        let a = semente >>> 0;
        return function () {
            a |= 0; a = (a + 0x6D2B79F5) | 0;
            let t = Math.imul(a ^ (a >>> 15), 1 | a);
            t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
            return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
        };
    }

    /* Teto "redondo" do eixo e os degraus até ele. */
    function degraus(max, quantos) {
        if (max <= 0) return [0, 1];
        const bruto = max / (quantos || 4);
        const pot = Math.pow(10, Math.floor(Math.log10(bruto)));
        const passo = [1, 2, 2.5, 5, 10].map((m) => m * pot).find((p) => p >= bruto);
        const lista = [];
        for (let v = 0; v < max + passo * 0.999; v += passo) lista.push(Math.round(v * 1000) / 1000);
        return lista;
    }

    /* ---------------------------------------------------------------- dica */

    let dica = null;

    function mostrarDica(evento, titulo, linhas) {
        if (!dica) dica = html('div', 'ug-dica', null, document.body);
        dica.replaceChildren();
        if (titulo) html('strong', null, titulo, dica);
        linhas.forEach((l) => {
            const p = html('p', null, null, dica);
            if (l.cor) { const m = html('i', null, null, p); m.style.background = l.cor; }
            html('span', null, l.rotulo, p);
            html('b', null, l.valor, p);
        });
        dica.hidden = false;
        const x = evento.clientX, y = evento.clientY;
        const r = dica.getBoundingClientRect();
        dica.style.left = Math.max(8, Math.min(window.innerWidth - r.width - 8, x + 14)) + 'px';
        dica.style.top = Math.max(8, y - r.height - 14 < 8 ? y + 18 : y - r.height - 14) + 'px';
    }

    function esconderDica() { if (dica) dica.hidden = true; }

    window.addEventListener('scroll', esconderDica, { passive: true });

    /* -------------------------------------------------------------- moldura */

    const vivos = new Set();
    let espera = null;
    if ('ResizeObserver' in window) {
        new ResizeObserver(() => {
            clearTimeout(espera);
            espera = setTimeout(() => vivos.forEach((h) => { if (h.isConnected && h.clientWidth !== h._ugLargura) h._ugDesenhar(); }), 120);
        }).observe(document.documentElement);
    }

    function montar(host, desenhar) {
        host._ugDesenhar = function () {
            host._ugLargura = host.clientWidth;
            host.replaceChildren();
            host.classList.add('ug');
            desenhar(Math.max(260, host.clientWidth));
        };
        vivos.add(host);
        host._ugDesenhar();
    }

    function legenda(host, series) {
        if (series.length < 2) return;
        const ul = html('ul', 'ug-legenda', null, host);
        series.forEach((s) => {
            const li = html('li', null, null, ul);
            const m = html('i', s.tracejada ? 'ug-traco' : null, null, li);
            m.style.background = s.cor;
            html('span', null, s.nome, li);
        });
    }

    /* A tabela com os mesmos números do gráfico. */
    function tabela(host, cabecalho, linhas, titulo) {
        const d = html('details', 'ug-tabela', null, host);
        html('summary', null, 'Ver em tabela', d);
        const rolagem = html('div', 'ug-tabela-rolagem', null, d);
        const t = html('table', null, null, rolagem);
        if (titulo) html('caption', null, titulo, t);
        const tr = html('tr', null, null, html('thead', null, null, t));
        cabecalho.forEach((c) => { const th = html('th', null, c, tr); th.scope = 'col'; });
        const corpo = html('tbody', null, null, t);
        linhas.forEach((l) => {
            const linha = html('tr', null, null, corpo);
            l.forEach((c, n) => { const cel = html(n === 0 ? 'th' : 'td', null, c, linha); if (n === 0) cel.scope = 'row'; });
        });
    }

    function texto(pai, x, y, conteudo, ancora, classe) {
        const t = svg('text', { x, y, 'text-anchor': ancora || 'start', class: classe || 'ug-eixo' }, pai);
        t.textContent = conteudo;
        return t;
    }

    /* Largura de um rótulo de 13px, medida num canvas (o SVG ainda não existe). */
    let regua = null;
    function larguraDoTexto(t) {
        if (!regua) {
            regua = document.createElement('canvas').getContext('2d');
            regua.font = '13px ' + (getComputedStyle(document.body).fontFamily || 'sans-serif');
        }
        return regua.measureText(t).width;
    }

    /* ---------------------------------------------------------------- linha */

    /**
     * cfg: { rotulos: [..], series: [{ nome, cor, valores: [..], tracejada }],
     *        formato, formatoEixo, altura, titulo, invertido }
     */
    function linha(host, cfg) {
        montar(host, (L) => {
            const A = cfg.altura || 260;
            const f = cfg.formato || fmt.int, fe = cfg.formatoEixo || fmt.compacto;
            legenda(host, cfg.series);

            const todos = cfg.series.flatMap((s) => s.valores.filter((v) => v !== null));
            const ticks = degraus(Math.max.apply(null, todos.concat([1])), 4);
            const teto = ticks[ticks.length - 1];
            const m = { e: 52, d: 16, c: 12, b: 28 };
            const w = L - m.e - m.d, h = A - m.c - m.b;
            const n = cfg.rotulos.length;
            const px = (i) => m.e + (n === 1 ? w / 2 : (i * w) / (n - 1));
            const py = (v) => m.c + h - (v / teto) * h;

            const s = svg('svg', { viewBox: `0 0 ${L} ${A}`, width: L, height: A, role: 'img', 'aria-label': cfg.titulo || 'Gráfico de linha' }, host);
            ticks.forEach((t) => {
                svg('line', { x1: m.e, x2: L - m.d, y1: py(t), y2: py(t), class: 'ug-grade' }, s);
                texto(s, m.e - 8, py(t) + 4, fe(t), 'end');
            });
            const pulo = Math.ceil(n / Math.max(2, Math.floor(w / 64)));
            // O último rótulo sempre entra; o vizinho que encostaria nele sai.
            cfg.rotulos.forEach((r, i) => {
                const ultimo = i === n - 1;
                if (ultimo || (i % pulo === 0 && n - 1 - i >= pulo)) texto(s, px(i), A - 8, r, ultimo ? 'end' : i === 0 ? 'start' : 'middle');
            });

            cfg.series.forEach((serie) => {
                let d = '', aberto = false, ultimo = -1;
                serie.valores.forEach((v, i) => {
                    if (v === null) { aberto = false; return; }
                    d += (aberto ? 'L' : 'M') + px(i).toFixed(1) + ' ' + py(v).toFixed(1);
                    aberto = true; ultimo = i;
                });
                const caminho = svg('path', { d, class: 'ug-linha' + (serie.tracejada ? ' ug-linha-meta' : '') }, s);
                caminho.style.stroke = serie.cor;
                if (ultimo >= 0 && !serie.tracejada) {
                    const p = svg('circle', { cx: px(ultimo), cy: py(serie.valores[ultimo]), r: 4.5, class: 'ug-ponto' }, s);
                    p.style.fill = serie.cor;
                }
            });

            // Mira: uma coluna invisível por ponto, bem maior que a marca.
            const mira = svg('line', { y1: m.c, y2: m.c + h, class: 'ug-mira', visibility: 'hidden' }, s);
            const alvo = svg('rect', { x: m.e, y: m.c, width: w, height: h, fill: 'transparent', tabindex: 0 }, s);
            const apontar = (ev) => {
                const caixa = s.getBoundingClientRect();
                const cx = (ev.touches ? ev.touches[0].clientX : ev.clientX) - caixa.left;
                const i = Math.max(0, Math.min(n - 1, Math.round(((cx - m.e) / w) * (n - 1))));
                mira.setAttribute('x1', px(i)); mira.setAttribute('x2', px(i)); mira.setAttribute('visibility', 'visible');
                mostrarDica(ev.touches ? ev.touches[0] : ev, cfg.rotulos[i], cfg.series.filter((x) => x.valores[i] !== null).map((x) => ({ cor: x.cor, rotulo: x.nome, valor: f(x.valores[i]) })));
            };
            alvo.addEventListener('mousemove', apontar);
            alvo.addEventListener('touchstart', apontar, { passive: true });
            alvo.addEventListener('mouseleave', () => { mira.setAttribute('visibility', 'hidden'); esconderDica(); });

            tabela(host, [cfg.eixoX || 'Período'].concat(cfg.series.map((x) => x.nome)),
                cfg.rotulos.map((r, i) => [r].concat(cfg.series.map((x) => (x.valores[i] === null ? '—' : f(x.valores[i]))))), cfg.titulo);
        });
    }

    /* --------------------------------------------------------------- barras */

    /**
     * Barras horizontais, uma por item, ordenadas por quem chama.
     * cfg: { itens: [{ rotulo, valor, detalhe }], cor, formato, titulo, colunas: ['Item', 'Valor'], teto }
     */
    function barras(host, cfg) {
        montar(host, (L) => {
            const f = cfg.formato || fmt.int;
            const max = cfg.teto || Math.max.apply(null, cfg.itens.map((i) => i.valor).concat([1]));
            // O rótulo fica ao lado da barra só quando CABE inteiro; senão sobe
            // para cima dela. Texto cortado ou saindo do gráfico não é opção.
            const maior = Math.max.apply(null, cfg.itens.map((i) => larguraDoTexto(i.rotulo)));
            const estreito = L < 420 || maior > L * 0.4;
            const larguraRotulo = estreito ? 0 : Math.ceil(maior) + 4;
            const passo = estreito ? 46 : 34, grossura = 18;
            const A = cfg.itens.length * passo + 4;
            const x0 = larguraRotulo + (estreito ? 0 : 10), folga = 74;
            const w = L - x0 - folga;

            const s = svg('svg', { viewBox: `0 0 ${L} ${A}`, width: L, height: A, role: 'img', 'aria-label': cfg.titulo || 'Gráfico de barras' }, host);
            cfg.itens.forEach((item, i) => {
                const y = i * passo + (estreito ? 20 : 8);
                if (estreito) texto(s, 0, y - 6, item.rotulo, 'start', 'ug-rotulo');
                else texto(s, larguraRotulo, y + grossura / 2 + 4, item.rotulo, 'end', 'ug-rotulo');
                const comprimento = Math.max(2, (item.valor / max) * w);
                // Ponta arredondada, base reta: a barra nasce da linha de base.
                const r = Math.min(4, comprimento);
                const b = svg('path', { d: `M${x0} ${y}h${comprimento - r}a${r} ${r} 0 0 1 ${r} ${r}v${grossura - 2 * r}a${r} ${r} 0 0 1 -${r} ${r}h-${comprimento - r}z`, class: 'ug-barra', tabindex: 0 }, s);
                b.style.fill = item.cor || cfg.cor || 'var(--serie-1)';
                texto(s, x0 + comprimento + 8, y + grossura / 2 + 4, f(item.valor), 'start', 'ug-valor');
                const alvo = svg('rect', { x: 0, y: y - 8, width: L, height: passo, fill: 'transparent' }, s);
                const dicas = [{ cor: item.cor || cfg.cor || 'var(--serie-1)', rotulo: (cfg.colunas || [])[1] || 'Valor', valor: f(item.valor) }];
                if (item.detalhe) dicas.push({ rotulo: item.detalhe[0], valor: item.detalhe[1] });
                alvo.addEventListener('mousemove', (ev) => mostrarDica(ev, item.rotulo, dicas));
                alvo.addEventListener('mouseleave', esconderDica);
            });
            tabela(host, cfg.colunas || ['Item', 'Valor'], cfg.itens.map((i) => [i.rotulo, f(i.valor)]), cfg.titulo);
        });
    }

    /* -------------------------------------------------------------- colunas */

    /** cfg: { rotulos, valores, cor, formato, formatoEixo, altura, titulo, colunas: ['Dia', 'Valor'] } */
    function colunas(host, cfg) {
        montar(host, (L) => {
            const A = cfg.altura || 220;
            const f = cfg.formato || fmt.int, fe = cfg.formatoEixo || fmt.compacto;
            const ticks = degraus(Math.max.apply(null, cfg.valores.concat([1])), 3);
            const teto = ticks[ticks.length - 1];
            const m = { e: 52, d: 8, c: 18, b: 28 };
            const w = L - m.e - m.d, h = A - m.c - m.b;
            const n = cfg.valores.length, faixa = w / n, grossura = Math.min(24, faixa - 2);
            const py = (v) => m.c + h - (v / teto) * h;

            const s = svg('svg', { viewBox: `0 0 ${L} ${A}`, width: L, height: A, role: 'img', 'aria-label': cfg.titulo || 'Gráfico de colunas' }, host);
            ticks.forEach((t) => {
                svg('line', { x1: m.e, x2: L - m.d, y1: py(t), y2: py(t), class: 'ug-grade' }, s);
                texto(s, m.e - 8, py(t) + 4, fe(t), 'end');
            });
            const maior = Math.max.apply(null, cfg.valores);
            cfg.valores.forEach((v, i) => {
                const x = m.e + i * faixa + (faixa - grossura) / 2, alto = Math.max(2, (v / teto) * h), r = Math.min(4, alto);
                const c = svg('path', { d: `M${x} ${m.c + h}v-${alto - r}a${r} ${r} 0 0 1 ${r} -${r}h${grossura - 2 * r}a${r} ${r} 0 0 1 ${r} ${r}v${alto - r}z`, class: 'ug-barra' }, s);
                c.style.fill = cfg.cor || 'var(--serie-1)';
                texto(s, x + grossura / 2, A - 8, cfg.rotulos[i], 'middle');
                // Rótulo só no maior: número em toda coluna ninguém lê.
                if (v === maior) texto(s, x + grossura / 2, py(v) - 6, f(v), 'middle', 'ug-valor');
                const alvo = svg('rect', { x: m.e + i * faixa, y: m.c, width: faixa, height: h, fill: 'transparent' }, s);
                alvo.addEventListener('mousemove', (ev) => mostrarDica(ev, cfg.rotulos[i], [{ cor: cfg.cor || 'var(--serie-1)', rotulo: (cfg.colunas || [])[1] || 'Valor', valor: f(v) }]));
                alvo.addEventListener('mouseleave', esconderDica);
            });
            tabela(host, cfg.colunas || ['Item', 'Valor'], cfg.rotulos.map((r, i) => [r, f(cfg.valores[i])]), cfg.titulo);
        });
    }

    /* ------------------------------------------------------- mapa de calor */

    /** cfg: { linhas: [..], colunas: [..], valores: [[..]], formato, titulo, medida } — uma cor só, do claro ao forte. */
    function calor(host, cfg) {
        montar(host, (L) => {
            const f = cfg.formato || fmt.int;
            const e = 44, c = 20, nl = cfg.linhas.length, nc = cfg.colunas.length;
            const lado = Math.min(34, (L - e) / nc), A = c + nl * lado + 4;
            const max = Math.max.apply(null, cfg.valores.flat().concat([1]));
            const s = svg('svg', { viewBox: `0 0 ${L} ${A}`, width: L, height: A, role: 'img', 'aria-label': cfg.titulo || 'Mapa de calor' }, host);
            cfg.colunas.forEach((r, j) => { if (nc <= 12 || j % 2 === 0) texto(s, e + j * lado + lado / 2, 12, r, 'middle'); });
            cfg.linhas.forEach((r, i) => {
                texto(s, e - 8, c + i * lado + lado / 2 + 4, r, 'end');
                cfg.colunas.forEach((col, j) => {
                    const v = cfg.valores[i][j];
                    // 2px de fundo entre as células: quem separa é o vão, não uma borda.
                    const q = svg('rect', { x: e + j * lado + 1, y: c + i * lado + 1, width: lado - 2, height: lado - 2, rx: 3, class: 'ug-celula' }, s);
                    q.style.fillOpacity = (0.08 + 0.92 * (v / max)).toFixed(3);
                    q.addEventListener('mousemove', (ev) => mostrarDica(ev, r + ', ' + col, [{ rotulo: cfg.medida || 'Valor', valor: f(v) }]));
                    q.addEventListener('mouseleave', esconderDica);
                });
            });
            const chave = html('p', 'ug-chave', null, host);
            html('span', null, 'menos', chave); html('i', null, null, chave); html('span', null, 'mais', chave);
            tabela(host, [''].concat(cfg.colunas), cfg.linhas.map((r, i) => [r].concat(cfg.valores[i].map(f))), cfg.titulo);
        });
    }

    /* ------------------------------------------------------------- utilidades */

    /** Liga um grupo de botões de filtro: um ativo por vez. */
    function filtro(grupo, aoEscolher) {
        const botoes = Array.from(grupo.querySelectorAll('button[data-valor]'));
        botoes.forEach((b) => b.addEventListener('click', () => {
            botoes.forEach((o) => o.setAttribute('aria-pressed', String(o === b)));
            aoEscolher(b.dataset.valor);
        }));
    }

    function preencher(seletor, valor) {
        const e = document.querySelector(seletor);
        if (e) e.textContent = valor;
    }

    window.UG = { linha, barras, colunas, calor, tabela, fmt, sorteio, filtro, preencher, html, svg, mostrarDica, esconderDica };
})();
