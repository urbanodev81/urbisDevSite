/*
 * Pergunte aos dados: manda a pergunta ao servidor do site (que fala com o
 * Sócrates) e mostra a resposta. Tudo o que volta entra por `textContent`:
 * é texto escrito por um modelo, nunca HTML.
 */
(function () {
    'use strict';
    const form = document.getElementById('pg-form');
    if (!form) return;
    const campo = document.getElementById('pg-pergunta');
    const botao = document.getElementById('pg-enviar');
    const saldo = document.getElementById('pg-saldo');
    const conversa = document.getElementById('pg-conversa');
    const token = document.querySelector('meta[name="csrf-token"]').content;
    let ocupado = false;

    function troca(pergunta, texto, aviso) {
        const caixa = document.createElement('div');
        caixa.className = 'pg-troca' + (aviso ? ' aviso' : '');
        const dt = document.createElement('dt'); dt.textContent = pergunta;
        const dd = document.createElement('dd'); dd.textContent = texto;
        caixa.append(dt, dd);
        conversa.prepend(caixa);
        return dd;
    }

    async function perguntar(pergunta) {
        pergunta = pergunta.trim();
        if (ocupado) return;
        if (pergunta.length < 3) { campo.focus(); return; }
        ocupado = true; botao.disabled = true; botao.textContent = 'Lendo a tabela…';
        const dd = troca(pergunta, 'Lendo a tabela…', false);
        let texto = 'Não foi possível falar com o servidor. Confira a conexão e tente de novo.', aviso = true;
        try {
            const r = await fetch('/demonstracoes/pergunte/perguntar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ pergunta }),
            });
            const corpo = await r.json().catch(() => null);
            if (r.status === 419) texto = 'A página ficou aberta tempo demais. Recarregue e pergunte de novo.';
            else if (r.status === 429 && !(corpo && corpo.texto)) texto = 'Muitas perguntas em pouco tempo. Espere um minuto e tente de novo.';
            else if (corpo && typeof corpo.texto === 'string') { texto = corpo.texto; aviso = !corpo.respondeu; }
            else if (corpo && corpo.errors && corpo.errors.pergunta) texto = corpo.errors.pergunta[0];
            if (corpo && typeof corpo.restantes === 'number') {
                saldo.textContent = corpo.restantes === 1 ? 'Resta 1 pergunta hoje.' : (corpo.restantes === 0 ? 'Você usou as perguntas de hoje.' : 'Restam ' + corpo.restantes + ' perguntas hoje.');
            }
            if (!aviso) campo.value = '';
        } catch (e) { /* fica o texto de falha de conexão */ }
        dd.textContent = texto;
        if (aviso) dd.parentNode.classList.add('aviso');
        ocupado = false; botao.disabled = false; botao.textContent = 'Perguntar';
    }

    form.addEventListener('submit', function (e) { e.preventDefault(); perguntar(campo.value); });
    campo.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); perguntar(campo.value); } });
    document.querySelectorAll('.pg-sugestoes button').forEach(function (b) {
        b.addEventListener('click', function () { campo.value = b.dataset.pergunta; perguntar(b.dataset.pergunta); });
    });
})();
