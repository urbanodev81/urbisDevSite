/* Demonstração: o relatório mensal que se escreve sozinho, a partir do ano de vendas da oficina. */
(function () {
    'use strict';
    const { colunas, fmt, preencher, html } = window.UG;
    const { MESES, VENDEDORES, SERVICOS, META, VENDAS } = window.UG_OFICINA;

    const NOME = { nov: 'novembro', dez: 'dezembro', jan: 'janeiro', fev: 'fevereiro', mar: 'março', abr: 'abril', mai: 'maio', jun: 'junho', jul: 'julho', ago: 'agosto', set: 'setembro', out: 'outubro' };
    const soma = (l) => l.reduce((s, v) => s + v.valor, 0);
    const doMes = (m) => VENDAS.filter((v) => v.mes === m);
    const pct = (parte, todo) => (todo ? (parte / todo) * 100 : 0);

    /** "12,4% acima de", "3,0% abaixo de" ou "no mesmo patamar de". */
    function contra(agora, antes) {
        const p = ((agora - antes) / antes) * 100;
        if (Math.abs(p) < 0.5) return 'no mesmo patamar de';
        return Math.abs(p).toFixed(1).replace('.', ',') + '% ' + (p > 0 ? 'acima de' : 'abaixo de');
    }

    function paragrafo(host, partes) {
        const p = html('p', null, null, host);
        partes.forEach((t) => (typeof t === 'string' ? p.appendChild(document.createTextNode(t)) : html('b', null, t[0], p)));
    }

    function linhaDaTabela(corpo, celulas) {
        const tr = html('tr', null, null, corpo);
        celulas.forEach((c, i) => { const e = html(i ? 'td' : 'th', null, c, tr); if (!i) e.scope = 'row'; });
    }

    function desenhar(m) {
        const lista = doMes(m), fat = soma(lista), meta = META[m];
        const porServico = SERVICOS.map((s, i) => { const l = lista.filter((v) => v.servico === i); return { nome: s[0], vendas: l.length, valor: soma(l) }; }).sort((a, b) => b.valor - a.valor);
        const porPessoa = VENDEDORES.map((n, i) => { const l = lista.filter((v) => v.vendedor === i); return { nome: n, vendas: l.length, valor: soma(l) }; }).sort((a, b) => b.valor - a.valor);
        const antes = m > 0 ? soma(doMes(m - 1)) : null;

        preencher('#re-titulo', 'Resultado de ' + NOME[MESES[m]]);
        preencher('#re-fat', fmt.brl(fat));
        preencher('#re-fat-d', antes === null ? 'primeiro mês da série' : contra(fat, antes) + ' ' + NOME[MESES[m - 1]]);
        preencher('#re-meta', fmt.pct(pct(fat, meta)));
        preencher('#re-meta-d', 'da meta de ' + fmt.brl(meta));
        preencher('#re-qtd', fmt.int(lista.length));
        preencher('#re-qtd-d', fmt.dec(lista.length / 26) + ' por dia útil');
        preencher('#re-ticket', fmt.brl(fat / Math.max(1, lista.length)));
        preencher('#re-ticket-d', 'por serviço vendido');

        // O texto sai dos mesmos números da tabela: nada aqui é escrito à mão.
        const texto = document.getElementById('re-texto');
        texto.replaceChildren();
        const falta = meta - fat;
        paragrafo(texto, [
            'Em ' + NOME[MESES[m]] + ' a oficina faturou ', [fmt.brl(fat)], ' em ', [fmt.int(lista.length) + ' serviços'],
            antes === null ? '.' : ', ' + contra(fat, antes) + ' ' + NOME[MESES[m - 1]] + '.',
            falta <= 0 ? ' A meta do mês foi batida, com ' : ' A meta do mês não foi batida: faltaram ', [fmt.brl(Math.abs(falta))], falta <= 0 ? ' de sobra.' : '.',
        ]);
        paragrafo(texto, [
            'O serviço que mais faturou foi ', [porServico[0].nome.toLowerCase()], ', com ' + fmt.pct(pct(porServico[0].valor, fat)) + ' do mês; em seguida vem ',
            [porServico[1].nome.toLowerCase()], '. Na equipe, ', [porPessoa[0].nome], ' respondeu por ' + fmt.pct(pct(porPessoa[0].valor, fat)) + ' do faturamento.',
        ]);

        const inicio = Math.max(0, m - 5);
        preencher('#re-evolucao-h', inicio === m ? 'O mês' : 'Os últimos ' + (m - inicio + 1) + ' meses');
        colunas(document.getElementById('re-evolucao'), {
            titulo: 'Faturamento por mês', colunas: ['Mês', 'Faturamento'], cor: 'var(--serie-1)', formato: fmt.brl,
            rotulos: MESES.slice(inicio, m + 1), valores: MESES.slice(inicio, m + 1).map((_, i) => soma(doMes(inicio + i))),
        });

        const servicos = document.getElementById('re-servicos'); servicos.replaceChildren();
        porServico.forEach((s) => linhaDaTabela(servicos, [s.nome, fmt.int(s.vendas), fmt.brl(s.valor), fmt.pct(pct(s.valor, fat))]));
        const equipe = document.getElementById('re-equipe'); equipe.replaceChildren();
        porPessoa.forEach((p) => linhaDaTabela(equipe, [p.nome, fmt.int(p.vendas), fmt.brl(p.valor), fmt.brl(p.valor / Math.max(1, p.vendas))]));
    }

    const sel = document.getElementById('re-mes');
    MESES.forEach((s, m) => { const o = html('option', null, NOME[s][0].toUpperCase() + NOME[s].slice(1), sel); o.value = m; });
    sel.value = String(MESES.length - 1);
    sel.addEventListener('change', () => desenhar(+sel.value));
    // O navegador é quem gera o arquivo: "Salvar como PDF" na janela de impressão.
    document.getElementById('re-pdf').addEventListener('click', () => window.print());
    // Papel é claro: quem está no tema escuro imprime com os tokens do claro, e
    // volta ao que era quando a janela de impressão fecha. Vale para o Ctrl+P.
    // `eraEscuro` só é decidido na primeira das chamadas: há navegador que avisa duas vezes.
    let eraEscuro = null;
    window.addEventListener('beforeprint', () => { if (eraEscuro === null) eraEscuro = !document.documentElement.classList.contains('claro'); document.documentElement.classList.add('claro'); });
    window.addEventListener('afterprint', () => { if (eraEscuro) document.documentElement.classList.remove('claro'); eraEscuro = null; });
    desenhar(MESES.length - 1);
})();
