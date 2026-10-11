/* Demonstração: painel comercial de uma oficina. Vendas e pessoas INVENTADAS. */
(function () {
    'use strict';
    const { linha, barras, colunas, fmt, sorteio, preencher, filtro } = window.UG;
    const r = sorteio(4471);

    const MESES = ['nov', 'dez', 'jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out'];
    const VENDEDORES = ['Marina', 'Rogério', 'Cíntia', 'Edu'];
    const SERVICOS = [['Revisão completa', 620], ['Troca de óleo e filtros', 240], ['Freios', 480], ['Suspensão', 890], ['Alinhamento e balanceamento', 160], ['Ar-condicionado', 350], ['Elétrica', 410]];
    const DIAS = ['seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
    const SAZONAL = [1.0, 1.25, 1.15, 0.8, 0.9, 0.95, 1.0, 1.1, 1.3, 1.05, 1.0, 1.1];
    const PESO_DIA = [1.2, 0.9, 0.85, 0.95, 1.15, 1.4];
    const META = [40000, 46000, 44000, 36000, 38000, 40000, 42000, 44000, 50000, 44000, 44000, 46000];

    const VENDAS = [];
    MESES.forEach((_, m) => {
        const quantas = Math.round(92 * SAZONAL[m] * (0.92 + r() * 0.16));
        for (let i = 0; i < quantas; i++) {
            const s = Math.floor(Math.pow(r(), 1.5) * SERVICOS.length);
            let d = 0, sorte = r() * PESO_DIA.reduce((a, b) => a + b, 0);
            while ((sorte -= PESO_DIA[d]) > 0) d++;
            VENDAS.push({ mes: m, vendedor: Math.floor(Math.pow(r(), 1.25) * VENDEDORES.length), servico: s, dia: d, valor: SERVICOS[s][1] * (0.75 + r() * 0.6) });
        }
    });

    const estado = { meses: 12, vendedor: 'todos' };

    function desenhar() {
        const inicio = 12 - estado.meses;
        const doPeriodo = VENDAS.filter((v) => v.mes >= inicio);
        const lista = doPeriodo.filter((v) => estado.vendedor === 'todos' || v.vendedor === +estado.vendedor);
        const anterior = VENDAS.filter((v) => v.mes >= inicio - estado.meses && v.mes < inicio && (estado.vendedor === 'todos' || v.vendedor === +estado.vendedor));
        const soma = (l) => l.reduce((s, v) => s + v.valor, 0);
        const fat = soma(lista), parte = estado.vendedor === 'todos' ? 1 : 0.25;
        const meta = META.slice(inicio).reduce((a, b) => a + b, 0) * parte;

        preencher('#co-fat', fmt.brl(fat));
        preencher('#co-fat-d', anterior.length ? variacao(fat, soma(anterior)) + ' contra o período anterior' : 'sem período anterior para comparar');
        preencher('#co-ticket', fmt.brl(fat / Math.max(1, lista.length))); preencher('#co-ticket-d', 'por serviço vendido');
        preencher('#co-qtd', fmt.int(lista.length)); preencher('#co-qtd-d', fmt.dec(lista.length / estado.meses / 26) + ' por dia útil');
        preencher('#co-meta', fmt.pct((fat / meta) * 100)); preencher('#co-meta-d', 'da meta de ' + fmt.brl(meta));

        const porMes = MESES.map((_, m) => soma(lista.filter((v) => v.mes === m))).slice(inicio);
        linha(document.getElementById('co-mensal'), {
            titulo: 'Faturamento por mês contra a meta', eixoX: 'Mês', formato: fmt.brl,
            rotulos: MESES.slice(inicio),
            series: [{ nome: 'Faturamento', cor: 'var(--serie-1)', valores: porMes }, { nome: 'Meta', cor: 'var(--fg-subtle)', valores: META.slice(inicio).map((v) => v * parte), tracejada: true }],
        });

        barras(document.getElementById('co-servicos'), {
            titulo: 'Faturamento por serviço', colunas: ['Serviço', 'Faturamento'], formato: fmt.brl, cor: 'var(--serie-1)',
            itens: SERVICOS.map((s, i) => { const l = lista.filter((v) => v.servico === i); return { rotulo: s[0], valor: soma(l), detalhe: ['Vendas', fmt.int(l.length)] }; }).sort((a, b) => b.valor - a.valor),
        });

        // Por vendedor vale o período inteiro: o filtro de pessoa não esconde os colegas aqui.
        barras(document.getElementById('co-vendedores'), {
            titulo: 'Faturamento por vendedor', colunas: ['Vendedor', 'Faturamento'], formato: fmt.brl, cor: 'var(--serie-3)',
            itens: VENDEDORES.map((n, i) => { const l = doPeriodo.filter((v) => v.vendedor === i); return { rotulo: n, valor: soma(l), detalhe: ['Ticket médio', fmt.brl(soma(l) / Math.max(1, l.length))] }; }).sort((a, b) => b.valor - a.valor),
        });

        colunas(document.getElementById('co-dias'), {
            titulo: 'Serviços vendidos por dia da semana', colunas: ['Dia', 'Serviços'], cor: 'var(--serie-2)', formato: fmt.int, formatoEixo: fmt.int,
            rotulos: DIAS, valores: DIAS.map((_, d) => lista.filter((v) => v.dia === d).length),
        });
    }

    function variacao(agora, antes) {
        const p = ((agora - antes) / antes) * 100;
        return (p >= 0 ? '+' : '−') + Math.abs(p).toFixed(1).replace('.', ',') + '%';
    }

    filtro(document.getElementById('co-periodo'), (v) => { estado.meses = +v; desenhar(); });
    const sel = document.getElementById('co-vendedor');
    VENDEDORES.forEach((n, i) => { const o = document.createElement('option'); o.value = i; o.textContent = n; sel.appendChild(o); });
    sel.addEventListener('change', () => { estado.vendedor = sel.value; desenhar(); });
    desenhar();
})();
