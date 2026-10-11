/*
 * Demonstração: suba a sua planilha.
 *
 * O arquivo do visitante é lido AQUI, no navegador: nenhuma linha deste script
 * faz requisição. CSV é texto; o .xlsx é um zip de XML, aberto com o
 * `DecompressionStream` do próprio navegador, sem biblioteca. Tudo o que vem do
 * arquivo entra na página por `textContent`, nunca como HTML.
 */
(function () {
    'use strict';
    const { linha, barras, fmt, preencher, filtro, sorteio, html } = window.UG;

    const TETO_BYTES = 5 * 1024 * 1024;
    const TETO_LINHAS = 20000;
    const TETO_COLUNAS = 60;
    const MESES = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

    const estado = { colunas: [], linhas: [], valor: '', conta: 'soma', grupo: '', data: '' };
    const el = (id) => document.getElementById(id);

    /* ------------------------------------------------------------------ CSV */

    function decodificar(bytes) {
        // Planilha exportada pelo Excel em português costuma vir em Windows-1252.
        try { return new TextDecoder('utf-8', { fatal: true }).decode(bytes); } catch (e) { return new TextDecoder('windows-1252').decode(bytes); }
    }

    function separadorDe(texto) {
        const primeira = texto.slice(0, texto.search(/\r?\n/) === -1 ? 4000 : texto.search(/\r?\n/));
        const conta = (c) => primeira.split(c).length - 1;
        const opcoes = [[';', conta(';')], ['\t', conta('\t')], [',', conta(',')]].sort((a, b) => b[1] - a[1]);
        return opcoes[0][1] > 0 ? opcoes[0][0] : ';';
    }

    function lerCsv(texto) {
        texto = texto.replace(/^﻿/, '');
        const sep = separadorDe(texto), linhas = [];
        let campo = '', atual = [], aspas = false;
        for (let i = 0; i < texto.length; i++) {
            const c = texto[i];
            if (aspas) {
                if (c === '"' && texto[i + 1] === '"') { campo += '"'; i++; } else if (c === '"') aspas = false; else campo += c;
            } else if (c === '"') aspas = true;
            else if (c === sep) { atual.push(campo); campo = ''; }
            else if (c === '\n' || c === '\r') {
                if (c === '\r' && texto[i + 1] === '\n') i++;
                atual.push(campo); campo = '';
                linhas.push(atual); atual = [];
                if (linhas.length > TETO_LINHAS + 1) break;
            } else campo += c;
        }
        if (campo !== '' || atual.length) { atual.push(campo); linhas.push(atual); }
        return linhas;
    }

    /* ----------------------------------------------------------------- XLSX */

    async function abrirZip(buffer) {
        const v = new DataView(buffer), bytes = new Uint8Array(buffer);
        let fim = -1;
        for (let i = buffer.byteLength - 22; i >= Math.max(0, buffer.byteLength - 66000); i--) {
            if (v.getUint32(i, true) === 0x06054b50) { fim = i; break; }
        }
        if (fim < 0) throw new Error('Este arquivo não parece ser um Excel (.xlsx).');
        const total = v.getUint16(fim + 10, true);
        let p = v.getUint32(fim + 16, true);
        const entradas = {};
        for (let n = 0; n < total && v.getUint32(p, true) === 0x02014b50; n++) {
            const metodo = v.getUint16(p + 10, true), comprimido = v.getUint32(p + 20, true);
            const nomeLen = v.getUint16(p + 28, true), extraLen = v.getUint16(p + 30, true), comentLen = v.getUint16(p + 32, true);
            const local = v.getUint32(p + 42, true);
            const nome = new TextDecoder().decode(bytes.subarray(p + 46, p + 46 + nomeLen));
            entradas[nome] = { metodo, comprimido, local };
            p += 46 + nomeLen + extraLen + comentLen;
        }
        return async function ler(nome) {
            const e = entradas[nome];
            if (!e) return null;
            const inicio = e.local + 30 + v.getUint16(e.local + 26, true) + v.getUint16(e.local + 28, true);
            const dados = bytes.subarray(inicio, inicio + e.comprimido);
            if (e.metodo === 0) return new TextDecoder().decode(dados);
            if (e.metodo !== 8) throw new Error('Este Excel usa uma compactação que a demonstração não abre.');
            const fluxo = new Blob([dados]).stream().pipeThrough(new DecompressionStream('deflate-raw'));
            return new TextDecoder().decode(await new Response(fluxo).arrayBuffer());
        };
    }

    const xml = (texto) => new DOMParser().parseFromString(texto, 'application/xml');

    function formatosDeData(estilos) {
        if (!estilos) return [];
        const doc = xml(estilos), proprios = {};
        Array.from(doc.getElementsByTagName('numFmt')).forEach((n) => { proprios[n.getAttribute('numFmtId')] = n.getAttribute('formatCode') || ''; });
        const xfs = doc.getElementsByTagName('cellXfs')[0];
        return Array.from(xfs ? xfs.getElementsByTagName('xf') : []).map((x) => {
            const id = +x.getAttribute('numFmtId');
            if ((id >= 14 && id <= 22) || (id >= 45 && id <= 47)) return true;
            const codigo = (proprios[id] || '').replace(/"[^"]*"|\[[^\]]*\]|\\./g, '');
            return /[dy]|m{1,5}/i.test(codigo) && !/[0#]/.test(codigo);
        });
    }

    async function lerXlsx(buffer) {
        if (typeof DecompressionStream === 'undefined') throw new Error('Este navegador não abre Excel por aqui. Salve a planilha como CSV e escolha de novo.');
        const ler = await abrirZip(buffer);
        const compartilhadas = [];
        const ss = await ler('xl/sharedStrings.xml');
        if (ss) {
            Array.from(xml(ss).getElementsByTagName('si')).forEach((si) => {
                compartilhadas.push(Array.from(si.getElementsByTagName('t')).filter((t) => t.parentNode.localName !== 'rPh').map((t) => t.textContent).join(''));
            });
        }
        const ehData = formatosDeData(await ler('xl/styles.xml'));
        // A primeira aba: `sheet1.xml` em toda planilha gravada pelo Excel, LibreOffice e Google.
        const aba = await ler('xl/worksheets/sheet1.xml');
        if (!aba) throw new Error('Não encontrei a primeira aba deste Excel.');
        const linhas = [];
        const rows = xml(aba).getElementsByTagName('row');
        for (let r = 0; r < rows.length && linhas.length <= TETO_LINHAS; r++) {
            const atual = [];
            const celulas = rows[r].getElementsByTagName('c');
            for (let k = 0; k < celulas.length; k++) {
                const c = celulas[k], ref = c.getAttribute('r') || '';
                let col = 0;
                for (let i = 0; i < ref.length && ref.charCodeAt(i) >= 65; i++) col = col * 26 + (ref.charCodeAt(i) - 64);
                col = ref ? col - 1 : k;
                if (col >= TETO_COLUNAS) continue;
                const tipo = c.getAttribute('t'), vEl = c.getElementsByTagName('v')[0], bruto = vEl ? vEl.textContent : '';
                let valor = bruto;
                if (tipo === 's') valor = compartilhadas[+bruto] || '';
                else if (tipo === 'inlineStr') valor = Array.from(c.getElementsByTagName('t')).map((t) => t.textContent).join('');
                else if (tipo === 'b') valor = bruto === '1' ? 'sim' : 'não';
                else if (!tipo || tipo === 'n') {
                    const n = parseFloat(bruto);
                    if (bruto !== '' && !isNaN(n)) valor = ehData[+c.getAttribute('s')] ? new Date(Date.UTC(1899, 11, 30) + Math.round(n * 86400000)) : n;
                }
                while (atual.length < col) atual.push('');
                atual[col] = valor;
            }
            linhas.push(atual);
        }
        return linhas;
    }

    /* ----------------------------------------------------- tabela e colunas */

    function comoNumero(v) {
        if (typeof v === 'number') return v;
        if (typeof v !== 'string') return NaN;
        const t = v.replace(/R\$|\s|%/g, '');
        if (/^-?\d{1,3}(\.\d{3})+(,\d+)?$/.test(t) || /^-?\d+,\d+$/.test(t)) return parseFloat(t.replace(/\./g, '').replace(',', '.'));
        if (/^-?\d{1,3}(,\d{3})+(\.\d+)?$/.test(t)) return parseFloat(t.replace(/,/g, ''));
        if (/^-?\d+(\.\d+)?$/.test(t)) return parseFloat(t);
        return NaN;
    }

    function comoData(v) {
        if (v instanceof Date) return isNaN(v) ? null : v;
        if (typeof v !== 'string') return null;
        let m = v.trim().match(/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{2}|\d{4})(?:[ T].*)?$/);
        if (m && +m[2] >= 1 && +m[2] <= 12 && +m[1] >= 1 && +m[1] <= 31) return new Date(Date.UTC(m[3].length === 2 ? 2000 + +m[3] : +m[3], +m[2] - 1, +m[1]));
        m = v.trim().match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T].*)?$/);
        if (m && +m[2] >= 1 && +m[2] <= 12) return new Date(Date.UTC(+m[1], +m[2] - 1, +m[3]));
        return null;
    }

    const vazio = (v) => v === undefined || v === null || v === '';

    function montarTabela(bruto) {
        const uteis = bruto.filter((l) => l.some((c) => !vazio(c) && String(c).trim() !== ''));
        if (uteis.length < 2) throw new Error('A planilha precisa de uma linha de títulos e pelo menos uma linha de dados.');
        const largura = Math.min(TETO_COLUNAS, Math.max.apply(null, uteis.slice(0, 200).map((l) => l.length)));
        const titulos = [];
        for (let i = 0; i < largura; i++) {
            const t = vazio(uteis[0][i]) ? '' : String(uteis[0][i] instanceof Date ? 'Data' : uteis[0][i]).trim();
            titulos.push((t || 'Coluna ' + (i + 1)).slice(0, 60));
        }
        const linhas = uteis.slice(1, TETO_LINHAS + 1);
        const amostra = linhas.slice(0, 500);
        const colunas = titulos.map((titulo, i) => {
            const valores = amostra.map((l) => l[i]).filter((v) => !vazio(v) && String(v).trim() !== '');
            const numeros = valores.filter((v) => !(v instanceof Date) && !isNaN(comoNumero(v))).length;
            const datas = valores.filter((v) => comoData(v)).length;
            const distintos = new Set(valores.map(String)).size;
            let tipo = 'texto';
            if (valores.length && datas / valores.length >= 0.8) tipo = 'data';
            else if (valores.length && numeros / valores.length >= 0.8) tipo = 'numero';
            const codigo = tipo === 'numero' && (/^(id|c[oó]d|n[ºo°.]|n[uú]mero|cpf|cnpj|cep|ano|telefone|fone)/i.test(titulo) || (distintos === valores.length && valores.length > 20 && valores.every((v) => Number.isInteger(comoNumero(v)))));
            return {
                i, titulo, tipo, distintos, preenchidos: valores.length, codigo,
                dinheiro: tipo === 'numero' && (/valor|pre[cç]o|total|fatur|receita|custo|venda|sal[aá]rio|r\$/i.test(titulo) || valores.some((v) => typeof v === 'string' && v.indexOf('R$') !== -1)),
                // Serve para agrupar: texto (ou número curto) que se repete.
                grupo: (tipo === 'texto' || (tipo === 'numero' && distintos <= 12)) && distintos >= 2 && distintos <= 40 && distintos < valores.length,
            };
        });
        return { colunas, linhas, cortada: uteis.length - 1 > TETO_LINHAS };
    }

    /* ---------------------------------------------------------------- painel */

    function opcoes(select, itens, escolhido) {
        select.textContent = '';
        itens.forEach(([valor, rotulo]) => { const o = document.createElement('option'); o.value = valor; o.textContent = rotulo; select.appendChild(o); });
        select.value = escolhido;
    }

    function mostrarCelula(v) {
        if (v instanceof Date) return v.toLocaleDateString('pt-BR', { timeZone: 'UTC' });
        if (typeof v === 'number') return v.toLocaleString('pt-BR', { maximumFractionDigits: 2 });
        return vazio(v) ? '' : String(v);
    }

    function desenhar() {
        const { colunas, linhas } = estado;
        const cv = estado.valor === '' ? null : colunas[+estado.valor], cg = estado.grupo === '' ? null : colunas[+estado.grupo], cd = estado.data === '' ? null : colunas[+estado.data];
        const contando = !cv;
        const medida = (l) => (contando ? 1 : comoNumero(l[cv.i]));
        const validas = linhas.filter((l) => !isNaN(medida(l)));
        const f = contando ? fmt.int : (cv.dinheiro ? fmt.brl : (n) => n.toLocaleString('pt-BR', { maximumFractionDigits: 1 }));
        const juntar = (lista) => {
            const soma = lista.reduce((s, l) => s + medida(l), 0);
            return estado.conta === 'media' && !contando ? soma / Math.max(1, lista.length) : soma;
        };
        const nomeDaMedida = contando ? 'Linhas' : (estado.conta === 'media' ? 'Média de ' : 'Soma de ') + cv.titulo;
        const total = validas.reduce((s, l) => s + medida(l), 0);

        preencher('#pl-linhas', fmt.int(linhas.length));
        preencher('#pl-linhas-d', colunas.length + ' colunas' + (estado.cortada ? ', só as primeiras ' + fmt.int(TETO_LINHAS) + ' linhas' : ''));
        preencher('#pl-n1-t', contando ? 'Linhas com dado' : 'Soma de ' + cv.titulo);
        preencher('#pl-n1', f(total));
        preencher('#pl-n1-d', contando ? 'nenhuma coluna de número para somar' : fmt.int(validas.length) + ' linhas com número');
        preencher('#pl-n2', contando ? 'n/d' : f(total / Math.max(1, validas.length)));
        preencher('#pl-n2-d', contando ? '' : 'de ' + cv.titulo);
        const maior = contando ? null : validas.reduce((m, l) => (m === null || medida(l) > medida(m) ? l : m), null);
        preencher('#pl-n3', maior ? f(medida(maior)) : 'n/d');
        preencher('#pl-n3-d', maior && cg ? mostrarCelula(maior[cg.i]) : '');

        // Por grupo
        let frase = 'Foram lidas ' + fmt.int(linhas.length) + ' linhas e ' + colunas.length + ' colunas.';
        el('pl-grupos-cartao').hidden = !cg;
        if (cg) {
            const mapa = new Map();
            validas.forEach((l) => { const k = vazio(l[cg.i]) || String(l[cg.i]).trim() === '' ? '(vazio)' : mostrarCelula(l[cg.i]); if (!mapa.has(k)) mapa.set(k, []); mapa.get(k).push(l); });
            let itens = Array.from(mapa, ([rotulo, lista]) => ({ rotulo, valor: juntar(lista), detalhe: ['Linhas', fmt.int(lista.length)] })).sort((a, b) => b.valor - a.valor);
            const resto = itens.slice(10);
            itens = itens.slice(0, 10);
            preencher('#pl-grupos-h', nomeDaMedida + ' por ' + cg.titulo);
            preencher('#pl-grupos-sub', resto.length ? 'Os 10 maiores de ' + (itens.length + resto.length) + '.' : 'Do maior para o menor.');
            barras(el('pl-grupos'), { titulo: nomeDaMedida + ' por ' + cg.titulo, colunas: [cg.titulo, nomeDaMedida], formato: f, cor: 'var(--serie-1)', itens });
            if (itens.length && total > 0 && estado.conta !== 'media') {
                frase += ' ' + itens[0].rotulo + ' responde por ' + fmt.pct((itens[0].valor / total) * 100) + (contando ? ' das linhas.' : ' de ' + cv.titulo + '.');
            }
        }

        // No tempo
        el('pl-tempo-cartao').hidden = !cd;
        if (cd) {
            const porMes = new Map();
            validas.forEach((l) => { const d = comoData(l[cd.i]); if (!d) return; const k = d.getUTCFullYear() * 12 + d.getUTCMonth(); if (!porMes.has(k)) porMes.set(k, []); porMes.get(k).push(l); });
            const chaves = Array.from(porMes.keys()).sort((a, b) => a - b);
            if (chaves.length < 2) el('pl-tempo-cartao').hidden = true;
            else {
                const anual = chaves[chaves.length - 1] - chaves[0] > 36;
                const baldes = new Map();
                chaves.forEach((k) => { const b = anual ? Math.floor(k / 12) : k; baldes.set(b, (baldes.get(b) || []).concat(porMes.get(k))); });
                const ordem = [];
                for (let b = Math.min.apply(null, Array.from(baldes.keys())); b <= Math.max.apply(null, Array.from(baldes.keys())); b++) ordem.push(b);
                const rotulos = ordem.map((b) => (anual ? String(b) : MESES[b % 12] + '/' + String(Math.floor(b / 12)).slice(2)));
                const valores = ordem.map((b) => (baldes.has(b) ? juntar(baldes.get(b)) : 0));
                preencher('#pl-tempo-h', nomeDaMedida + (anual ? ' ano a ano' : ' mês a mês'));
                preencher('#pl-tempo-sub', 'Pela coluna ' + cd.titulo + ', de ' + rotulos[0] + ' a ' + rotulos[rotulos.length - 1] + '.');
                linha(el('pl-tempo'), { titulo: nomeDaMedida + ' no tempo', eixoX: anual ? 'Ano' : 'Mês', formato: f, rotulos, series: [{ nome: nomeDaMedida, cor: 'var(--serie-1)', valores }] });
                const pico = valores.indexOf(Math.max.apply(null, valores));
                frase += ' O ponto mais alto foi ' + rotulos[pico] + ', com ' + f(valores[pico]) + '.';
            }
        }
        preencher('#pl-frase', frase);
    }

    function previa() {
        const host = el('pl-previa');
        host.textContent = '';
        const t = html('table', null, null, host), cab = html('tr', null, null, html('thead', null, null, t)), corpo = html('tbody', null, null, t);
        const nomes = { numero: 'número', data: 'data', texto: 'texto' };
        estado.colunas.forEach((c) => { const th = html('th', null, c.titulo, cab); th.scope = 'col'; html('small', null, nomes[c.tipo], th); });
        estado.linhas.slice(0, 8).forEach((l) => { const tr = html('tr', null, null, corpo); estado.colunas.forEach((c) => html('td', null, mostrarCelula(l[c.i]), tr)); });
        preencher('#pl-previa-sub', 'As 8 primeiras linhas, com o tipo que a demonstração entendeu de cada coluna.');
    }

    function abrir(bruto, nome) {
        const tabela = montarTabela(bruto);
        estado.colunas = tabela.colunas; estado.linhas = tabela.linhas; estado.cortada = tabela.cortada;
        const numeros = tabela.colunas.filter((c) => c.tipo === 'numero' && !c.codigo);
        const grupos = tabela.colunas.filter((c) => c.grupo).sort((a, b) => a.distintos - b.distintos);
        const datas = tabela.colunas.filter((c) => c.tipo === 'data');
        const preferido = numeros.find((c) => c.dinheiro) || numeros[numeros.length - 1];
        estado.valor = preferido ? String(preferido.i) : '';
        estado.grupo = grupos.length ? String((grupos.find((c) => c.tipo === 'texto') || grupos[0]).i) : '';
        estado.data = datas.length ? String(datas[0].i) : '';
        estado.conta = 'soma';
        opcoes(el('pl-valor'), numeros.map((c) => [String(c.i), c.titulo]).concat([['', 'Quantidade de linhas']]), estado.valor);
        opcoes(el('pl-grupo'), grupos.map((c) => [String(c.i), c.titulo]).concat([['', 'Não agrupar']]), estado.grupo);
        opcoes(el('pl-data'), datas.map((c) => [String(c.i), c.titulo]).concat([['', 'Sem data']]), estado.data);
        el('pl-data-campo').hidden = !datas.length;
        el('pl-conta').querySelectorAll('button').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.valor === 'soma')));
        el('pl-painel').hidden = false;
        previa();
        desenhar();
        preencher('#pl-status', 'Lido: ' + nome + ', ' + fmt.int(tabela.linhas.length) + ' linhas. Nada saiu deste aparelho.');
        el('pl-painel').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function falhar(mensagem) {
        const e = el('pl-erro');
        e.textContent = mensagem; e.hidden = false;
        preencher('#pl-status', '');
    }

    async function receber(arquivo) {
        el('pl-erro').hidden = true;
        if (!arquivo) return;
        if (arquivo.size > TETO_BYTES) return falhar('Este arquivo tem mais de 5 MB. Para a demonstração, escolha um recorte menor.');
        preencher('#pl-status', 'Lendo ' + arquivo.name + '…');
        try {
            const buffer = await arquivo.arrayBuffer();
            const zip = new Uint8Array(buffer, 0, Math.min(2, buffer.byteLength));
            if (/\.xls$/i.test(arquivo.name)) throw new Error('Este é o formato antigo do Excel (.xls). Salve como .xlsx ou CSV e escolha de novo.');
            abrir(zip[0] === 0x50 && zip[1] === 0x4b ? await lerXlsx(buffer) : lerCsv(decodificar(buffer)), arquivo.name);
        } catch (erro) {
            falhar(erro && erro.message ? erro.message : 'Não consegui ler este arquivo.');
        }
    }

    /* Planilha de exemplo: vendas INVENTADAS, para quem não tem um arquivo à mão. */
    function exemplo() {
        const r = sorteio(20261011);
        const vendedores = ['Aline', 'Bruno', 'Carla', 'Diego'], produtos = ['Plano Básico', 'Plano Completo', 'Instalação', 'Suporte avulso', 'Treinamento'], precos = [390, 890, 1500, 250, 1200];
        const linhas = [['Data', 'Vendedor', 'Produto', 'Região', 'Valor']];
        for (let i = 0; i < 320; i++) {
            const mes = Math.floor(r() * 12), p = Math.floor(r() * r() * produtos.length);
            linhas.push([new Date(Date.UTC(2026, mes, 1 + Math.floor(r() * 28))), vendedores[Math.floor(r() * r() * 4)], produtos[p], ['Capital', 'Interior', 'Litoral'][Math.floor(r() * 3)], Math.round(precos[p] * (0.8 + r() * 0.5) * (1 + mes / 30))]);
        }
        return linhas;
    }

    /* ---------------------------------------------------------------- ligações */

    const entrada = el('pl-arquivo'), solta = el('pl-soltar');
    entrada.addEventListener('change', () => { receber(entrada.files[0]); entrada.value = ''; });
    ['dragenter', 'dragover'].forEach((t) => solta.addEventListener(t, (e) => { e.preventDefault(); solta.classList.add('sobre'); }));
    ['dragleave', 'drop'].forEach((t) => solta.addEventListener(t, (e) => { e.preventDefault(); solta.classList.remove('sobre'); }));
    solta.addEventListener('drop', (e) => receber(e.dataTransfer.files[0]));
    el('pl-exemplo').addEventListener('click', () => { el('pl-erro').hidden = true; abrir(exemplo(), 'planilha de exemplo (dados inventados)'); });

    el('pl-valor').addEventListener('change', (e) => { estado.valor = e.target.value; desenhar(); });
    el('pl-grupo').addEventListener('change', (e) => { estado.grupo = e.target.value; desenhar(); });
    el('pl-data').addEventListener('change', (e) => { estado.data = e.target.value; desenhar(); });
    filtro(el('pl-conta'), (v) => { estado.conta = v; desenhar(); });
})();
