/* Demonstração: painel comercial de uma oficina. Vendas e pessoas INVENTADAS. */
(function () {
    'use strict';
    const { linha, barras, colunas, fmt, preencher, filtro } = window.UG;
    const { MESES, VENDEDORES, SERVICOS, DIAS, META, VENDAS } = window.UG_OFICINA;

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
