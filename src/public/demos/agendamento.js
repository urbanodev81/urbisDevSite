/* Demonstração: agendamento online de uma oficina. Agenda e clientes INVENTADOS; nada sai do navegador. */
(function () {
    'use strict';
    const { sorteio, html, preencher } = window.UG;

    // [nome, duração em minutos, a partir de]
    const SERVICOS = [['Troca de óleo e filtros', 30, 240], ['Alinhamento e balanceamento', 60, 160], ['Revisão completa', 120, 620], ['Freios', 90, 480], ['Ar-condicionado', 60, 350], ['Diagnóstico elétrico', 60, 180]];
    const CLIENTES = ['Paulo · Onix 2019', 'Renata · HB20 2021', 'Seu Jorge · Corolla 2015', 'Bia · Kwid 2022', 'Marcos · Hilux 2018', 'Dona Lúcia · Fit 2014', 'André · Polo 2020', 'Carla · Compass 2021'];
    const SEMANA = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
    const MES = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
    const PASSO = 30, ABRE = 8 * 60, FECHA = 18 * 60, FECHA_SABADO = 12 * 60;

    const hora = (min) => String(Math.floor(min / 60)).padStart(2, '0') + ':' + String(min % 60).padStart(2, '0');
    const duracao = (min) => (min < 60 ? min + ' min' : (min / 60).toLocaleString('pt-BR') + ' h');

    /** Monta a agenda depois de saber os feriados; sem resposta, segue sem eles. */
    function montar(feriados) {
        // Os próximos seis dias em que a oficina abre, a partir de amanhã. Feriado
        // nacional (por lei, da BrasilAPI pelo servidor do site) entra na fila
        // FECHADO: o visitante vê que a agenda sabe do feriado.
        const iso = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        const DIAS = [];
        for (let d = new Date(), i = 0, abertos = 0; abertos < 6 && i < 14; i++) {
            d = new Date(d.getFullYear(), d.getMonth(), d.getDate() + 1);
            if (d.getDay() === 0) continue;
            DIAS.push(d);
            if (!feriados[iso(d)]) abertos++;
        }

        // A agenda já ocupada de cada dia. A semente é o dia da semana: quem volta amanhã vê outra agenda, mas ela não muda a cada clique.
        const AGENDA = DIAS.map((dia) => {
            const r = sorteio(900 + dia.getDay() * 37 + dia.getDate());
            const fim = dia.getDay() === 6 ? FECHA_SABADO : FECHA, marcados = [];
            for (let t = ABRE; t < fim;) {
                if (r() < 0.55) {
                    const s = SERVICOS[Math.floor(r() * SERVICOS.length)];
                    if (t + s[1] <= fim) { marcados.push({ inicio: t, fim: t + s[1], servico: s[0], quem: CLIENTES[Math.floor(r() * CLIENTES.length)] }); t += s[1]; continue; }
                }
                t += PASSO;
            }
            return feriados[iso(dia)] ? { dia, fim: ABRE, marcados: [], feriado: feriados[iso(dia)] } : { dia, fim, marcados };
        });

        const estado = { servico: 0, dia: Math.max(0, AGENDA.findIndex((a) => !a.feriado)), inicio: null };

        const livre = (agenda, inicio, minutos) => inicio + minutos <= agenda.fim && !agenda.marcados.some((m) => inicio < m.fim && inicio + minutos > m.inicio);
        const horarios = (agenda) => { const l = []; for (let t = ABRE; t < agenda.fim; t += PASSO) l.push(t); return l; };
        const livres = (agenda, minutos) => horarios(agenda).filter((t) => livre(agenda, t, minutos)).length;
        const porExtenso = (dia) => SEMANA[dia.getDay()] + ', ' + dia.getDate() + ' de ' + MES[dia.getMonth()];

        function botao(pai, classe, pressionado, aoClicar) {
            const b = html('button', classe, null, pai);
            b.type = 'button';
            b.setAttribute('aria-pressed', String(pressionado));
            b.addEventListener('click', aoClicar);
            return b;
        }

        function desenhar() {
            const minutos = SERVICOS[estado.servico][1], agenda = AGENDA[estado.dia];
            // Os botões são refeitos a cada escolha: quem navega pelo teclado volta ao mesmo botão, não ao topo da página.
            const foco = document.activeElement, grupo = foco && foco.parentElement && /^ag-(servicos|dias|horas)$/.test(foco.parentElement.id) ? foco.parentElement : null;
            const posicao = grupo ? Array.prototype.indexOf.call(grupo.children, foco) : -1;
            if (estado.inicio !== null && !livre(agenda, estado.inicio, minutos)) estado.inicio = null;

            const servicos = document.getElementById('ag-servicos'); servicos.replaceChildren();
            SERVICOS.forEach((s, i) => {
                const b = botao(servicos, 'ag-opcao', i === estado.servico, () => { estado.servico = i; desenhar(); });
                html('span', null, s[0], b); html('small', null, duracao(s[1]) + ' · desde R$ ' + s[2], b);
            });

            const dias = document.getElementById('ag-dias'); dias.replaceChildren();
            AGENDA.forEach((a, i) => {
                const vagas = livres(a, minutos);
                const b = botao(dias, 'ag-dia', i === estado.dia, () => { estado.dia = i; estado.inicio = null; desenhar(); });
                html('span', null, SEMANA[a.dia.getDay()], b); html('b', null, String(a.dia.getDate()), b);
                html('small', null, a.feriado ? 'feriado' : vagas ? vagas + (vagas === 1 ? ' vaga' : ' vagas') : 'lotado', b);
                b.setAttribute('aria-label', porExtenso(a.dia) + ', ' + (a.feriado ? 'fechado: ' + a.feriado : vagas ? vagas + ' horários livres' : 'lotado'));
                if (a.feriado) { b.disabled = true; b.title = a.feriado; }
            });

            const horas = document.getElementById('ag-horas'); horas.replaceChildren();
            horarios(agenda).forEach((t) => {
                const b = botao(horas, 'ag-hora', t === estado.inicio, () => { estado.inicio = t; desenhar(); });
                b.textContent = hora(t);
                if (!livre(agenda, t, minutos)) { b.disabled = true; b.setAttribute('aria-label', hora(t) + ', ocupado'); }
            });

            const marcar = document.getElementById('ag-marcar');
            marcar.disabled = estado.inicio === null;
            marcar.textContent = estado.inicio === null ? 'Escolha um horário' : 'Marcar para ' + porExtenso(agenda.dia) + ' às ' + hora(estado.inicio);

            desenharAgenda();
            if (grupo && grupo.children[posicao]) grupo.children[posicao].focus();
        }

        /** O lado de quem atende: o dia escolhido, de meia em meia hora, com o que já estava marcado e o que acabou de entrar. */
        function desenharAgenda() {
            const agenda = AGENDA[estado.dia], lista = document.getElementById('ag-agenda');
            lista.replaceChildren();
            preencher('#ag-oficina-sub', 'O dia ' + porExtenso(agenda.dia) + ', como a oficina enxerga.');
            const ocupado = agenda.marcados.reduce((s, m) => s + (m.fim - m.inicio), 0);
            preencher('#ag-resumo', agenda.marcados.length + ' atendimentos · ' + Math.round((ocupado / (agenda.fim - ABRE)) * 100) + '% do dia ocupado');
            for (let t = ABRE; t < agenda.fim; t += PASSO) {
                const m = agenda.marcados.find((x) => x.inicio === t);
                if (!m && agenda.marcados.some((x) => t > x.inicio && t < x.fim)) continue;
                const li = html('li', m ? (m.novo ? 'novo' : 'ocupado') : null, null, lista);
                html('time', null, hora(t), li);
                if (!m) { html('span', 'livre', 'livre', li); continue; }
                const d = html('div', null, m.quem, li);
                html('small', null, m.servico + ' · até ' + hora(m.fim) + (m.novo ? ' · marcado agora pelo site' : ''), d);
                t = m.fim - PASSO;
            }
        }

        /** Arquivo de calendário montado aqui mesmo: é só texto, nenhum servidor participa. */
        function calendario(dia, inicio, fim, servico) {
            const carimbo = (min) => dia.getFullYear() + String(dia.getMonth() + 1).padStart(2, '0') + String(dia.getDate()).padStart(2, '0') + 'T' + hora(min).replace(':', '') + '00';
            const linhas = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//UrbisDev//Demonstracao//PT', 'BEGIN:VEVENT', 'UID:demo-' + carimbo(inicio) + '@urbisdev.tech', 'DTSTAMP:' + carimbo(inicio), 'DTSTART:' + carimbo(inicio), 'DTEND:' + carimbo(fim), 'SUMMARY:' + servico + ' (demonstração UrbisDev)', 'END:VEVENT', 'END:VCALENDAR'];
            return 'data:text/calendar;charset=utf-8,' + encodeURIComponent(linhas.join('\r\n'));
        }

        document.getElementById('ag-marcar').addEventListener('click', () => {
            if (estado.inicio === null) return;
            const s = SERVICOS[estado.servico], agenda = AGENDA[estado.dia], inicio = estado.inicio;
            // O que o visitante digita só é escrito como texto na página dele (textContent), nunca interpretado.
            const nome = document.getElementById('ag-nome').value.trim() || 'Você', carro = document.getElementById('ag-carro').value.trim();
            agenda.marcados.push({ inicio, fim: inicio + s[1], servico: s[0] + (bairroLevaETraz ? ' · buscar em ' + bairroLevaETraz : ''), quem: carro ? nome + ' · ' + carro : nome, novo: true });
            preencher('#ag-confirmado-t', 'Marcado: ' + s[0] + ', ' + porExtenso(agenda.dia) + ' às ' + hora(inicio) + '.');
            preencher('#ag-confirmado-d', 'O horário saiu da lista de livres e entrou na agenda ao lado. Num site de verdade, você e a oficina receberiam a confirmação por e-mail ou WhatsApp.');
            document.getElementById('ag-ics').href = calendario(agenda.dia, inicio, inicio + s[1], s[0]);
            document.getElementById('ag-confirmado').hidden = false;
            estado.inicio = null;
            desenhar();
        });

        /** CEP → endereço do leva e traz (ViaCEP, com a BrasilAPI de reserva, pelo servidor do site). */
        let bairroLevaETraz = '';
        const cep = document.getElementById('ag-cep'), endereco = document.getElementById('ag-endereco');
        cep.addEventListener('input', async () => {
            const d = cep.value.replace(/\D/g, '').slice(0, 8);
            cep.value = d.length > 5 ? d.slice(0, 5) + '-' + d.slice(5) : d;
            bairroLevaETraz = '';
            if (d.length !== 8) { endereco.textContent = ''; return; }
            endereco.textContent = 'Procurando o endereço…';
            try {
                const r = await fetch('/demonstracoes/dados/cep/' + d, { headers: { Accept: 'application/json' } });
                if (cep.value.replace(/\D/g, '') !== d) return;
                const j = r.ok ? await r.json() : null;
                if (!j) { endereco.textContent = 'CEP não encontrado. Confira os números.'; return; }
                bairroLevaETraz = j.bairro || j.cidade;
                endereco.textContent = [j.logradouro, j.bairro].filter(Boolean).join(', ') + ' — ' + j.cidade + '/' + j.uf;
            } catch (err) {
                endereco.textContent = 'Não deu para consultar o CEP agora.';
            }
        });

        document.getElementById('ag-outro').addEventListener('click', () => {
            document.getElementById('ag-confirmado').hidden = true;
            document.getElementById('ag-servicos').querySelector('button').focus();
        });

        desenhar();
    }

    // Mais de 2,5 s sem resposta: a agenda monta sem feriado, não fica em branco.
    const espera = new AbortController();
    setTimeout(() => espera.abort(), 2500);
    fetch('/demonstracoes/dados/feriados', { headers: { Accept: 'application/json' }, signal: espera.signal })
        .then((r) => (r.ok ? r.json() : []))
        .catch(() => [])
        .then((lista) => montar(Object.fromEntries((Array.isArray(lista) ? lista : []).map((f) => [f.data, f.nome]))));
})();
