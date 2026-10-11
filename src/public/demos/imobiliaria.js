/* Demonstração: funil de uma imobiliária. Leads e imóveis INVENTADOS. */
(function () {
    'use strict';
    const { linha, barras, fmt, sorteio, preencher, filtro, html } = window.UG;
    const r = sorteio(9102);

    const ORIGENS = ['Site', 'Portais', 'WhatsApp', 'Indicação', 'Placa'];
    const PESO = [0.3, 0.34, 0.2, 0.1, 0.06];
    // Chance de cada origem avançar de etapa: indicação fecha mais, portal menos.
    const AVANCO = [[0.62, 0.42, 0.3, 0.42], [0.5, 0.3, 0.22, 0.3], [0.7, 0.45, 0.3, 0.4], [0.85, 0.65, 0.45, 0.55], [0.6, 0.5, 0.35, 0.45]];
    const ETAPAS = ['Leads', 'Atendidos', 'Visitaram', 'Fizeram proposta', 'Fecharam'];
    const RESPOSTA = [38, 95, 12, 55, 140];
    const SEMANAS = 12;

    const LEADS = [];
    for (let s = 0; s < SEMANAS; s++) {
        const quantos = Math.round(46 + r() * 22 + s * 0.8);
        for (let i = 0; i < quantos; i++) {
            let o = 0, sorte = r(); while ((sorte -= PESO[o]) > 0 && o < ORIGENS.length - 1) o++;
            let etapa = 0; while (etapa < 4 && r() < AVANCO[o][etapa]) etapa++;
            LEADS.push({ semana: s, origem: o, etapa, minutos: RESPOSTA[o] * (0.3 + Math.pow(r(), 2) * 2.6) * (1.25 - s * 0.03) });
        }
    }

    const BAIRROS = ['Vila Guilherme', 'Santana', 'Tucuruvi', 'Vila Maria', 'Casa Verde', 'Mandaqui'];
    const TIPOS = ['Apartamento 2 dorms', 'Casa 3 dorms', 'Sobrado', 'Apartamento 3 dorms', 'Studio', 'Sala comercial'];
    const IMOVEIS = Array.from({ length: 8 }, (_, i) => {
        const visitas = Math.round(900 - i * 85 + r() * 120);
        return { nome: TIPOS[i % TIPOS.length] + ', ' + BAIRROS[i % BAIRROS.length], codigo: 'UD-' + (1040 + i * 7), visitas, contatos: Math.round(visitas * (0.008 + r() * 0.05)) };
    });

    const estado = { semanas: 12, origem: 'todas' };

    function desenhar() {
        const inicio = SEMANAS - estado.semanas;
        const periodo = LEADS.filter((l) => l.semana >= inicio);
        const lista = periodo.filter((l) => estado.origem === 'todas' || l.origem === +estado.origem);
        const noFunil = ETAPAS.map((_, e) => lista.filter((l) => l.etapa >= e).length);
        const atendidos = lista.filter((l) => l.etapa >= 1);
        const mediana = (l) => { const o = l.map((x) => x.minutos).sort((a, b) => a - b); return o.length ? o[Math.floor(o.length / 2)] : 0; };

        preencher('#im-leads', fmt.int(lista.length)); preencher('#im-leads-d', fmt.dec(lista.length / estado.semanas) + ' por semana');
        preencher('#im-resp', fmt.min(mediana(atendidos))); preencher('#im-resp-d', 'metade dos leads esperou menos que isso');
        preencher('#im-visita', fmt.pct((noFunil[2] / Math.max(1, noFunil[0])) * 100)); preencher('#im-visita-d', noFunil[2] + ' visitas marcadas');
        preencher('#im-fecha', fmt.pct((noFunil[4] / Math.max(1, noFunil[0])) * 100)); preencher('#im-fecha-d', noFunil[4] + ' negócios fechados');

        barras(document.getElementById('im-funil'), {
            titulo: 'Funil, do lead ao negócio fechado', colunas: ['Etapa', 'Leads'], cor: 'var(--serie-1)',
            itens: ETAPAS.map((n, e) => ({ rotulo: n, valor: noFunil[e], detalhe: ['Do total', fmt.pct((noFunil[e] / Math.max(1, noFunil[0])) * 100)] })),
        });

        // Por origem vale o período inteiro: é a comparação que o filtro de origem esconderia.
        barras(document.getElementById('im-origens'), {
            titulo: 'Leads por origem', colunas: ['Origem', 'Leads'], cor: 'var(--serie-3)',
            itens: ORIGENS.map((n, o) => { const l = periodo.filter((x) => x.origem === o); return { rotulo: n, valor: l.length, detalhe: ['Fecharam', fmt.pct((l.filter((x) => x.etapa >= 4).length / Math.max(1, l.length)) * 100)] }; }).sort((a, b) => b.valor - a.valor),
        });

        linha(document.getElementById('im-resposta'), {
            titulo: 'Tempo até a primeira resposta, por semana', eixoX: 'Semana', formato: fmt.min, formatoEixo: (n) => Math.round(n) + ' min',
            rotulos: Array.from({ length: estado.semanas }, (_, i) => 'sem ' + (inicio + i + 1)),
            series: [{ nome: 'Tempo de resposta (mediana)', cor: 'var(--serie-2)', valores: Array.from({ length: estado.semanas }, (_, i) => mediana(atendidos.filter((l) => l.semana === inicio + i))) }],
        });

        // Muito visto e pouco contatado é o anúncio que precisa de atenção.
        const corpo = document.querySelector('#im-imoveis tbody');
        corpo.replaceChildren();
        const maxV = Math.max.apply(null, IMOVEIS.map((i) => i.visitas));
        IMOVEIS.forEach((i) => {
            const taxa = (i.contatos / i.visitas) * 100;
            const tr = html('tr', null, null, corpo);
            const th = html('th', null, null, tr); th.scope = 'row';
            html('span', null, i.nome, th); html('small', null, i.codigo, th);
            const v = html('td', 'im-barra', null, tr); const b = html('i', null, null, v); b.style.width = Math.round((i.visitas / maxV) * 100) + '%'; html('span', null, fmt.int(i.visitas), v);
            html('td', null, fmt.int(i.contatos), tr);
            const t = html('td', null, null, tr);
            html('span', null, taxa.toFixed(1).replace('.', ',') + '%', t);
            if (taxa < 1.5) html('em', 'im-alerta', 'Rever o anúncio', t);
        });
    }

    filtro(document.getElementById('im-periodo'), (v) => { estado.semanas = +v; desenhar(); });
    const sel = document.getElementById('im-origem');
    ORIGENS.forEach((n, i) => { const o = document.createElement('option'); o.value = i; o.textContent = n; sel.appendChild(o); });
    sel.addEventListener('change', () => { estado.origem = sel.value; desenhar(); });
    desenhar();
})();
