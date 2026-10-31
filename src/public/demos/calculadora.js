/* Calculadora: quanto a planilha custa por mês. Só os números do visitante; nada sai do navegador. */
(function () {
    'use strict';
    const { barras, fmt, preencher } = window.UG;

    // Mês médio: 52 semanas ÷ 12. Mês de trabalho: 22 dias × 8 h = 176 h, a
    // régua usual para tirar o valor da hora de um salário mensal.
    const SEMANAS_POR_MES = 52 / 12;
    const HORAS_DE_TRABALHO_NO_MES = 176;

    const campo = (id) => document.getElementById(id);
    const numero = (id) => {
        const v = parseFloat(String(campo(id).value).replace(',', '.'));
        return Number.isFinite(v) && v > 0 ? v : 0;
    };
    const reais = (n) => 'R$ ' + n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function calcular() {
        const pessoas = numero('calc-pessoas');
        const semana = numero('calc-semana');
        const salario = numero('calc-salario');
        const encargos = numero('calc-encargos');
        const erros = numero('calc-erros');
        const custoErro = numero('calc-custo-erro');
        const automatiza = numero('calc-automatiza') / 100;

        const valorHora = (salario * (1 + encargos / 100)) / HORAS_DE_TRABALHO_NO_MES;
        const horasMes = pessoas * semana * SEMANAS_POR_MES;
        const custoTempo = horasMes * valorHora;
        const custoErros = erros * custoErro;
        const mes = custoTempo + custoErros;
        // O sistema tira a parte repetitiva do tempo e, junto, os erros de
        // digitação e de versão que ela produz: os dois na mesma proporção.
        const volta = mes * automatiza;
        const horasDeVolta = horasMes * automatiza;

        preencher('#calc-mes', fmt.brl(mes));
        preencher('#calc-horas', fmt.int(horasMes) + ' h');
        preencher('#calc-horas-d', horasMes >= 8 ? '≈ ' + fmt.int(horasMes / 8) + ' dias de trabalho' : 'menos de um dia de trabalho');
        preencher('#calc-ano', fmt.brl(mes * 12));
        preencher('#calc-volta', fmt.brl(volta));
        preencher('#calc-volta-d', fmt.int(horasDeVolta) + ' h livres por mês, ' + fmt.brl(volta * 12) + ' no ano');
        preencher('#calc-automatiza-v', fmt.pct(automatiza * 100));
        preencher('#calc-hora', 'Cada hora custa ' + reais(valorHora) + ' para a empresa: salário + ' + fmt.int(encargos) + '% ÷ ' + HORAS_DE_TRABALHO_NO_MES + ' h.');

        barras(campo('calc-barras'), {
            titulo: 'Custo mensal da planilha',
            colunas: ['Parte', 'Por mês'],
            formato: fmt.brl,
            teto: Math.max(mes, 1),
            itens: [
                { rotulo: 'Tempo da equipe', valor: custoTempo, detalhe: ['Horas', fmt.int(horasMes) + ' h'] },
                { rotulo: 'Erros e retrabalho', valor: custoErros, cor: 'var(--serie-2)', detalhe: ['Erros', fmt.int(erros)] },
                { rotulo: 'Dá para recuperar', valor: volta, cor: 'var(--serie-3)', detalhe: ['Parte repetitiva', fmt.pct(automatiza * 100)] },
            ],
        });

        // A conta por extenso: quem desconfia do número vê de onde ele vem.
        const conta = campo('calc-conta');
        conta.replaceChildren();
        [
            ['Tempo: ', fmt.int(pessoas) + ' pessoa(s) × ' + semana.toLocaleString('pt-BR') + ' h por semana × 4,33 semanas × ' + reais(valorHora) + ' = ', reais(custoTempo)],
            ['Erros: ', fmt.int(erros) + ' × ' + reais(custoErro) + ' = ', reais(custoErros)],
            ['Recuperável: ', fmt.pct(automatiza * 100) + ' de ' + reais(mes) + ' = ', reais(volta)],
        ].forEach(([rotulo, meio, fim], i) => {
            if (i) conta.appendChild(document.createElement('br'));
            conta.append(rotulo + meio);
            const b = document.createElement('b');
            b.textContent = fim;
            conta.appendChild(b);
        });
    }

    campo('calc-form').addEventListener('input', calcular);
    campo('calc-form').addEventListener('submit', (ev) => ev.preventDefault());
    calcular();
})();
