/* Demonstração: campeonato de futebol. Times, jogos e jogadores INVENTADOS. */
(function () {
    'use strict';
    const { linha, barras, fmt, sorteio, preencher, html, svg, mostrarDica, esconderDica } = window.UG;
    const r = sorteio(20261011);

    const TIMES = ['Atlético Aurora', 'Porto Real', 'Vila Nova do Sul', 'Estrela do Vale', 'União Serrana',
        'Ferroviário Central', 'Marítimo Azul', 'Guarani da Mata', 'Operário Leste', 'Juventude Praiana'];
    const SOBRENOMES = ['Tavares', 'Moura', 'Peixoto', 'Brandão', 'Siqueira', 'Lacerda', 'Falcão', 'Rezende', 'Quintino', 'Barcelos',
        'Paiva', 'Coutinho', 'Meireles', 'Sardinha', 'Vilela', 'Amaral', 'Bezerra', 'Caldas', 'Dorneles', 'Esteves',
        'Fontoura', 'Galvão', 'Hermes', 'Itamar', 'Jardim', 'Lobato', 'Macedo', 'Noronha', 'Ortega', 'Pimentel'];
    const forca = TIMES.map(() => 0.8 + r() * 1.1);
    const elenco = TIMES.map((_, t) => [0, 1, 2].map((j) => ({ nome: SOBRENOMES[t * 3 + j], time: t, peso: [0.5, 0.3, 0.2][j] })));

    function poisson(media) { let l = Math.exp(-media), k = 0, p = 1; do { k++; p *= r(); } while (p > l); return k - 1; }

    // Turno e returno pelo método do círculo: 18 rodadas, 5 jogos cada.
    const RODADAS = [];
    const roda = TIMES.map((_, i) => i);
    for (let volta = 0; volta < 2; volta++) {
        for (let n = 0; n < TIMES.length - 1; n++) {
            const jogos = [];
            for (let i = 0; i < TIMES.length / 2; i++) {
                let casa = roda[i], fora = roda[TIMES.length - 1 - i];
                if ((n + i + volta) % 2) [casa, fora] = [fora, casa];
                // Mando de campo: quem joga em casa marca mais.
                const gc = poisson(1.2 * forca[casa] / forca[fora] + 0.3), gf = poisson(0.85 * forca[fora] / forca[casa]);
                const quem = (t, g) => Array.from({ length: g }, () => { const s = r(); return elenco[t][s < 0.5 ? 0 : s < 0.8 ? 1 : 2]; });
                const chutes = (t, g) => Array.from({ length: g + 4 + Math.floor(r() * 7) }, (_, k) => ({ time: t, x: 8 + r() * 84, y: 6 + Math.pow(r(), 1.6) * 62, gol: k < g }));
                jogos.push({ casa, fora, gc, gf, gols: quem(casa, gc).concat(quem(fora, gf)), chutes: chutes(casa, gc).concat(chutes(fora, gf)) });
            }
            RODADAS.push(jogos);
            roda.splice(1, 0, roda.pop());
        }
    }

    function classificacao(ate) {
        const t = TIMES.map((nome, i) => ({ i, nome, p: 0, j: 0, v: 0, e: 0, d: 0, gp: 0, gc: 0, casa: [0, 0], fora: [0, 0], serie: [] }));
        RODADAS.slice(0, ate).forEach((jogos) => {
            jogos.forEach((g) => {
                const a = t[g.casa], b = t[g.fora];
                a.j++; b.j++; a.gp += g.gc; a.gc += g.gf; b.gp += g.gf; b.gc += g.gc;
                const pa = g.gc > g.gf ? 3 : g.gc === g.gf ? 1 : 0, pb = g.gf > g.gc ? 3 : g.gc === g.gf ? 1 : 0;
                a.p += pa; b.p += pb; a.casa[0] += pa; a.casa[1] += 3; b.fora[0] += pb; b.fora[1] += 3;
                [[a, pa], [b, pb]].forEach(([x, p]) => { if (p === 3) x.v++; else if (p === 1) x.e++; else x.d++; });
            });
            t.forEach((x) => x.serie.push(x.p));
        });
        return t.slice().sort((a, b) => b.p - a.p || b.v - a.v || (b.gp - b.gc) - (a.gp - a.gc) || b.gp - a.gp || a.nome.localeCompare(b.nome));
    }

    const estado = { rodada: RODADAS.length, a: 0, b: 1 };

    function desenhar() {
        const tab = classificacao(estado.rodada);
        const antes = estado.rodada > 1 ? classificacao(estado.rodada - 1).map((x) => x.i) : null;
        const jogos = RODADAS.slice(0, estado.rodada).flat();
        const gols = jogos.reduce((s, g) => s + g.gc + g.gf, 0);

        const artilharia = {};
        jogos.forEach((g) => g.gols.forEach((j) => { const k = j.nome + '|' + j.time; artilharia[k] = (artilharia[k] || 0) + 1; }));
        const top = Object.entries(artilharia).map(([k, v]) => ({ nome: k.split('|')[0], time: +k.split('|')[1], gols: v }))
            .sort((x, y) => y.gols - x.gols || x.nome.localeCompare(y.nome)).slice(0, 8);
        const ataque = tab.slice().sort((x, y) => y.gp - x.gp)[0];

        preencher('#fb-rodada-n', estado.rodada + ' de ' + RODADAS.length);
        preencher('#fb-lider', tab[0].nome); preencher('#fb-lider-d', tab[0].p + ' pontos em ' + tab[0].j + ' jogos');
        preencher('#fb-ataque', ataque.nome); preencher('#fb-ataque-d', ataque.gp + ' gols marcados');
        preencher('#fb-media', fmt.dec(gols / jogos.length)); preencher('#fb-media-d', gols + ' gols em ' + jogos.length + ' jogos');
        preencher('#fb-art', top[0] ? top[0].nome : '—'); preencher('#fb-art-d', top[0] ? top[0].gols + ' gols, ' + TIMES[top[0].time] : '');

        // Tabela de classificação, com quem subiu e quem caiu na rodada.
        const corpo = document.querySelector('#fb-tabela tbody');
        corpo.replaceChildren();
        tab.forEach((x, pos) => {
            const tr = html('tr', x.i === estado.a ? 'fb-a' : x.i === estado.b ? 'fb-b' : null, null, corpo);
            const mov = antes ? antes.indexOf(x.i) - pos : 0;
            const c = html('td', 'fb-pos', null, tr);
            html('b', null, String(pos + 1), c);
            const seta = html('span', mov > 0 ? 'sobe' : mov < 0 ? 'desce' : 'fica', mov > 0 ? '▲' : mov < 0 ? '▼' : '–', c);
            seta.setAttribute('aria-label', mov > 0 ? 'subiu ' + mov : mov < 0 ? 'caiu ' + (-mov) : 'manteve');
            const th = html('th', null, x.nome, tr); th.scope = 'row';
            [x.p, x.j, x.v, x.e, x.d, x.gp, x.gc, x.gp - x.gc].forEach((v, n) => html('td', n === 0 ? 'fb-pts' : null, String(v), tr));
        });

        const A = tab.find((x) => x.i === estado.a), B = tab.find((x) => x.i === estado.b);
        linha(document.getElementById('fb-corrida'), {
            titulo: 'Pontos acumulados por rodada', eixoX: 'Rodada', formato: fmt.int, formatoEixo: fmt.int,
            rotulos: A.serie.map((_, i) => String(i + 1)),
            series: [{ nome: A.nome, cor: 'var(--serie-1)', valores: A.serie }, { nome: B.nome, cor: 'var(--serie-2)', valores: B.serie }],
        });

        const pct = (par) => (par[1] ? Math.round((par[0] / par[1]) * 100) + '%' : '—');
        const comp = document.querySelector('#fb-comparador tbody');
        comp.replaceChildren();
        [['Posição', tab.indexOf(A) + 1 + 'º', tab.indexOf(B) + 1 + 'º'], ['Pontos', A.p, B.p], ['Vitórias', A.v, B.v], ['Gols marcados', A.gp, B.gp],
            ['Gols sofridos', A.gc, B.gc], ['Aproveitamento em casa', pct(A.casa), pct(B.casa)], ['Aproveitamento fora', pct(A.fora), pct(B.fora)]]
            .forEach((l) => { const tr = html('tr', null, null, comp); const th = html('th', null, l[0], tr); th.scope = 'row'; html('td', null, String(l[1]), tr); html('td', null, String(l[2]), tr); });
        preencher('#fb-comp-a', A.nome); preencher('#fb-comp-b', B.nome);

        barras(document.getElementById('fb-artilharia'), {
            titulo: 'Artilharia', colunas: ['Jogador', 'Gols'], cor: 'var(--serie-1)',
            itens: top.map((j) => ({ rotulo: j.nome, valor: j.gols, detalhe: ['Time', TIMES[j.time]] })),
        });

        campo(jogos.flatMap((g) => g.chutes.filter((c) => c.time === estado.a)), A.nome);
    }

    /* Mapa de finalizações: meio campo de ataque. Gol é bolinha cheia, o resto
       é anel — a forma diz o mesmo que a cor, para quem não distingue as duas. */
    function campo(chutes, nome) {
        const host = document.getElementById('fb-campo');
        host.replaceChildren();
        const s = svg('svg', { viewBox: '0 0 100 72', role: 'img', 'aria-label': 'Finalizações de ' + nome + ' no campo de ataque', class: 'fb-gramado' }, host);
        svg('rect', { x: 1, y: 1, width: 98, height: 70, class: 'fb-linha' }, s);
        svg('rect', { x: 22, y: 1, width: 56, height: 24, class: 'fb-linha' }, s);
        svg('rect', { x: 37, y: 1, width: 26, height: 9, class: 'fb-linha' }, s);
        svg('path', { d: 'M40 25a10 10 0 0 0 20 0', class: 'fb-linha' }, s);
        svg('path', { d: 'M40 71a10 10 0 0 1 20 0', class: 'fb-linha' }, s);
        chutes.filter((c) => !c.gol).concat(chutes.filter((c) => c.gol)).forEach((c) => {
            const p = svg('circle', { cx: c.x, cy: c.y, r: c.gol ? 1.7 : 1.3, class: c.gol ? 'fb-gol' : 'fb-chute' }, s);
            p.addEventListener('mousemove', (ev) => mostrarDica(ev, c.gol ? 'Gol' : 'Finalização sem gol', [{ rotulo: 'Distância do gol', valor: Math.round(c.y * 0.55 + 2) + ' m' }]));
            p.addEventListener('mouseleave', esconderDica);
        });
        const g = chutes.filter((c) => c.gol).length;
        preencher('#fb-campo-resumo', nome + ': ' + chutes.length + ' finalizações, ' + g + ' gols (' + Math.round((g / Math.max(1, chutes.length)) * 100) + '% de conversão).');
    }

    const faixa = document.getElementById('fb-rodada');
    faixa.max = RODADAS.length; faixa.value = estado.rodada;
    faixa.addEventListener('input', () => { estado.rodada = +faixa.value; desenhar(); });
    ['a', 'b'].forEach((k) => {
        const sel = document.getElementById('fb-time-' + k);
        TIMES.forEach((nome, i) => { const o = html('option', null, nome, sel); o.value = i; });
        sel.value = estado[k];
        sel.addEventListener('change', () => { estado[k] = +sel.value; desenhar(); });
    });
    desenhar();
})();
