/*
 * O ano de vendas da oficina de demonstração. Vendas e pessoas INVENTADAS.
 *
 * Mora à parte porque duas páginas leem o mesmo ano: o painel comercial e o
 * relatório mensal. Semente fixa: as duas mostram os mesmos números.
 */
(function () {
    'use strict';
    const r = window.UG.sorteio(4471);

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

    window.UG_OFICINA = { MESES, VENDEDORES, SERVICOS, DIAS, META, VENDAS };
})();
