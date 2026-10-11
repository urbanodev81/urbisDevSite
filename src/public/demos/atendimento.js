/* Demonstração: atendimento e prazos de um suporte. Chamados INVENTADOS. */
(function () {
    'use strict';
    const { linha, barras, calor, fmt, sorteio, preencher, filtro } = window.UG;
    const r = sorteio(77031);

    const FILAS = ['Financeiro', 'Suporte técnico', 'Cadastro', 'Comercial'];
    const PESO = [0.2, 0.45, 0.2, 0.15];
    const PRAZO = [240, 120, 480, 180];            // minutos combinados para a primeira resposta
    const TIPICO = [150, 105, 260, 90];
    const DIAS = ['seg', 'ter', 'qua', 'qui', 'sex'];
    const HORAS = ['8h', '9h', '10h', '11h', '12h', '13h', '14h', '15h', '16h', '17h'];
    const PESO_DIA = [1.5, 1.1, 0.95, 0.9, 1.05];
    const PESO_HORA = [0.7, 1.4, 1.5, 1.2, 0.5, 0.7, 1.3, 1.2, 0.9, 0.6];
    const SEMANAS = 8;

    const sortear = (pesos) => { let i = 0, s = r() * pesos.reduce((a, b) => a + b, 0); while ((s -= pesos[i]) > 0 && i < pesos.length - 1) i++; return i; };

    const CHAMADOS = [];
    for (let s = 0; s < SEMANAS; s++) {
        const quantos = Math.round(170 + r() * 50);
        for (let i = 0; i < quantos; i++) {
            const f = sortear(PESO), dia = sortear(PESO_DIA), hora = sortear(PESO_HORA);
            // Segunda de manhã acumula: a fila demora mais justamente quando mais chega.
            const aperto = 1 + (PESO_DIA[dia] * PESO_HORA[hora] - 1) * 0.35;
            const minutos = TIPICO[f] * (0.25 + Math.pow(r(), 1.7) * 2.4) * aperto;
            CHAMADOS.push({ semana: s, fila: f, dia, hora, minutos, aberto: s === SEMANAS - 1 && r() < 0.22 });
        }
    }

    const estado = { semanas: 8, fila: 'todas' };

    function desenhar() {
        const inicio = SEMANAS - estado.semanas;
        const periodo = CHAMADOS.filter((c) => c.semana >= inicio);
        const lista = periodo.filter((c) => estado.fila === 'todas' || c.fila === +estado.fila);
        const noPrazo = (l) => l.filter((c) => c.minutos <= PRAZO[c.fila]).length;
        const mediana = (l) => { const o = l.map((x) => x.minutos).sort((a, b) => a - b); return o.length ? o[Math.floor(o.length / 2)] : 0; };
        const abertos = lista.filter((c) => c.aberto);

        preencher('#at-total', fmt.int(lista.length)); preencher('#at-total-d', fmt.int(lista.length / estado.semanas) + ' por semana');
        preencher('#at-prazo', fmt.pct((noPrazo(lista) / Math.max(1, lista.length)) * 100)); preencher('#at-prazo-d', (lista.length - noPrazo(lista)) + ' passaram do combinado');
        preencher('#at-resp', fmt.min(mediana(lista))); preencher('#at-resp-d', 'até a primeira resposta (mediana)');
        preencher('#at-abertos', fmt.int(abertos.length)); preencher('#at-abertos-d', abertos.filter((c) => c.minutos > PRAZO[c.fila]).length + ' já fora do prazo');

        linha(document.getElementById('at-semanal'), {
            titulo: 'Respondidos dentro do prazo, por semana', eixoX: 'Semana', formato: fmt.pct, formatoEixo: fmt.pct,
            rotulos: Array.from({ length: estado.semanas }, (_, i) => 'sem ' + (inicio + i + 1)),
            series: [
                { nome: 'Dentro do prazo', cor: 'var(--serie-1)', valores: Array.from({ length: estado.semanas }, (_, i) => { const l = lista.filter((c) => c.semana === inicio + i); return (noPrazo(l) / Math.max(1, l.length)) * 100; }) },
                { nome: 'Combinado (90%)', cor: 'var(--fg-subtle)', valores: Array.from({ length: estado.semanas }, () => 90), tracejada: true },
            ],
        });

        // Por fila vale o período inteiro, para a fila escolhida ter com quem se comparar.
        barras(document.getElementById('at-filas'), {
            titulo: 'Dentro do prazo, por fila', colunas: ['Fila', 'Dentro do prazo'], formato: fmt.pct, teto: 100, cor: 'var(--serie-3)',
            itens: FILAS.map((n, f) => { const l = periodo.filter((c) => c.fila === f); return { rotulo: n, valor: (noPrazo(l) / Math.max(1, l.length)) * 100, detalhe: ['Chamados', fmt.int(l.length)] }; }).sort((a, b) => a.valor - b.valor),
        });

        calor(document.getElementById('at-calor'), {
            titulo: 'Chamados por dia da semana e hora', medida: 'Chamados', linhas: DIAS, colunas: HORAS,
            valores: DIAS.map((_, d) => HORAS.map((__, h) => lista.filter((c) => c.dia === d && c.hora === h).length)),
        });
    }

    filtro(document.getElementById('at-periodo'), (v) => { estado.semanas = +v; desenhar(); });
    const sel = document.getElementById('at-fila');
    FILAS.forEach((n, i) => { const o = document.createElement('option'); o.value = i; o.textContent = n; sel.appendChild(o); });
    sel.addEventListener('change', () => { estado.fila = sel.value; desenhar(); });
    desenhar();
})();
