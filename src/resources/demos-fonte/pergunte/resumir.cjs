// Tira de `public/demos/dados-oficina.js` (o mesmo ano de vendas do painel
// comercial e do relatório) o resumo que a demonstração "Pergunte aos dados"
// mostra na página e manda para a IA: `resources/demos/oficina-resumo.json`.
// O servidor não roda o gerador do navegador; por isso o resumo é um arquivo.
// Rodar de novo sempre que `dados-oficina.js` mudar:
//   node resources/demos-fonte/pergunte/resumir.cjs
const fs = require('fs'), path = require('path'), vm = require('vm');
const raiz = path.join(__dirname, '../../..');
const ler = (f) => fs.readFileSync(path.join(raiz, 'public/demos', f), 'utf8');
const sorteio = ler('graficos.js').match(/function sorteio\(semente\) \{[\s\S]*?\n    \}\n/)[0];
const window = {}; vm.runInNewContext(sorteio + '; window.UG = { sorteio };' + ler('dados-oficina.js'), { window, Math });
const { MESES, VENDEDORES, SERVICOS, DIAS, META, VENDAS } = window.UG_OFICINA;
const soma = (l) => Math.round(l.reduce((a, v) => a + v.valor, 0));
const porMes = (filtro) => MESES.map((_, m) => VENDAS.filter((v) => v.mes === m && filtro(v)));
const resumo = {
    meses: MESES,
    mensal: porMes(() => true).map((l, m) => ({ mes: MESES[m], faturamento: soma(l), meta: META[m], vendas: l.length })),
    servicos: SERVICOS.map(([nome], s) => ({ nome, faturamento: porMes((v) => v.servico === s).map(soma), vendas: porMes((v) => v.servico === s).map((l) => l.length) })),
    vendedores: VENDEDORES.map((nome, i) => ({ nome, faturamento: porMes((v) => v.vendedor === i).map(soma), vendas: porMes((v) => v.vendedor === i).map((l) => l.length) })),
    dias: DIAS.map((dia, d) => ({ dia, faturamento: soma(VENDAS.filter((v) => v.dia === d)), vendas: VENDAS.filter((v) => v.dia === d).length })),
};
fs.writeFileSync(path.join(raiz, 'resources/demos/oficina-resumo.json'), JSON.stringify(resumo, null, 1) + '\n');
const total = resumo.mensal.reduce((a, m) => a + m.faturamento, 0);
console.log('vendas', VENDAS.length, 'faturamento do ano', total, 'soma por serviço', resumo.servicos.reduce((a, s) => a + s.faturamento.reduce((x, y) => x + y, 0), 0));
