/* Demonstração: galeria de sites que troca de tipo, nome, cor e logo ao vivo. Negócios INVENTADOS; o logo não sai do navegador. */
(function () {
    'use strict';
    const { html, filtro, preencher } = window.UG;

    const TIPOS = {
        oficina: {
            rotulo: 'Oficina', nome: 'Auto Center Modelo', menu: ['Serviços', 'Galeria', 'Onde fica'], chamada: 'Oficina mecânica',
            frase: 'Seu carro na mão de quem entende.', texto: 'Revisão, freios, suspensão e elétrica, com orçamento antes de mexer e garantia no serviço.',
            acao: 'Agendar revisão', acao2: 'Ver serviços',
            itens: [['Orçamento sem compromisso', 'Você aprova antes de qualquer peça ser trocada.'], ['Agenda pelo site', 'Escolha o dia e o horário livre, sem ligar.'], ['Fotos do serviço', 'Antes e depois, direto da oficina.']],
            desenho: 'M3 13l2-5a2 2 0 0 1 2-1h10a2 2 0 0 1 2 1l2 5v5h-3v-2H6v2H3zM6.5 13h11M7 16h.01M17 16h.01',
        },
        banda: {
            rotulo: 'Banda', nome: 'Os Modelos', menu: ['Agenda', 'Músicas', 'Contratar'], chamada: 'Banda de rock',
            frase: 'Som ao vivo para a sua noite.', texto: 'Agenda de shows, vídeos, repertório e o contato para contratar, tudo num lugar só.',
            acao: 'Ver a agenda', acao2: 'Ouvir',
            itens: [['Próximos shows', 'A agenda sempre em dia, atualizada pela própria banda.'], ['Vídeos e fotos', 'O material de divulgação pronto para o contratante.'], ['Pedido de orçamento', 'O contato chega organizado, com data e local.']],
            desenho: 'M9 18V5l11-2v13M9 18a3 3 0 1 1-6 0 3 3 0 0 1 6 0zM20 16a3 3 0 1 1-6 0 3 3 0 0 1 6 0z',
        },
        clinica: {
            rotulo: 'Clínica', nome: 'Clínica Modelo', menu: ['Tratamentos', 'Equipe', 'Marcar'], chamada: 'Fisioterapia',
            frase: 'Volte a se mexer sem dor.', texto: 'Atendimento individual, horários pela manhã e à noite e avaliação marcada pelo site.',
            acao: 'Marcar avaliação', acao2: 'Conhecer a equipe',
            itens: [['Tratamentos explicados', 'O que é, para quem serve e quanto tempo leva.'], ['Marcação online', 'O paciente escolhe o horário e recebe a confirmação.'], ['Acessível de verdade', 'Contraste, teclado e Libras, desde o primeiro dia.']],
            desenho: 'M12 21s-7-4.5-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 11c0 5.500-7 10-7 10zM9 11h2V9h2v2h2v2h-2v2h-2v-2H9z',
        },
        imobiliaria: {
            rotulo: 'Imobiliária', nome: 'Imóveis Modelo', menu: ['Comprar', 'Alugar', 'Lançamentos'], chamada: 'Imobiliária',
            frase: 'O imóvel certo, sem perder tempo.', texto: 'Busca por bairro e valor, fichas com fotos e mapa, e o corretor avisado a cada contato.',
            acao: 'Buscar imóveis', acao2: 'Anunciar o meu',
            itens: [['Carteira sempre atual', 'O que sai do sistema da imobiliária aparece no site.'], ['Lançamentos com ficha própria', 'Plantas, galeria e vídeo de cada empreendimento.'], ['Contato que vira atendimento', 'Cada recado chega com o imóvel e a origem.']],
            desenho: 'M3 11l9-7 9 7M5 10v10h14V10M10 20v-6h4v6',
        },
    };
    const CORES = [['Laranja', '#c2410c'], ['Vermelho', '#b91c1c'], ['Verde', '#15803d'], ['Azul', '#1d4ed8'], ['Roxo', '#7e22ce'], ['Grafite', '#334155'], ['Amarelo', '#facc15']];

    const estado = { tipo: 'oficina', cor: CORES[0][1], tema: 'claro', nomeDigitado: false, cidade: '' };
    const site = document.getElementById('gs-site'), campoNome = document.getElementById('gs-nome');
    const logoImg = document.getElementById('gs-logo-img'), logoCampo = document.getElementById('gs-logo'), logoTirar = document.getElementById('gs-logo-tirar');
    let logoUrl = null;

    /** Preto ou branco, o que tiver mais contraste sobre a cor escolhida (luminância relativa da WCAG). */
    function sobre(hex) {
        const canal = (i) => { const v = parseInt(hex.slice(i, i + 2), 16) / 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); };
        const l = 0.2126 * canal(1) + 0.7152 * canal(3) + 0.0722 * canal(5);
        return (1.05 / (l + 0.05)) >= ((l + 0.05) / 0.05) ? '#ffffff' : '#111111';
    }

    function desenhar() {
        const t = TIPOS[estado.tipo], nome = campoNome.value.trim() || t.nome;
        site.classList.toggle('escuro', estado.tema === 'escuro');
        site.style.setProperty('--m', estado.cor);
        site.style.setProperty('--sobre-m', sobre(estado.cor));

        preencher('#gs-marca-nome', nome);
        preencher('#gs-sinal', nome.charAt(0).toUpperCase());
        document.getElementById('gs-sinal').hidden = !!logoUrl;
        preencher('#gs-endereco', 'www.' + (nome.normalize('NFD').replace(/[^a-zA-Z0-9]/g, '').toLowerCase() || 'seunegocio') + '.com.br');
        preencher('#gs-chamada', t.chamada); preencher('#gs-frase', t.frase); preencher('#gs-texto', t.texto);
        preencher('#gs-acao', t.acao); preencher('#gs-acao2', t.acao2);
        preencher('#gs-pe', nome + (estado.cidade ? ' · ' + estado.cidade : '') + ' · site de exemplo');

        const menu = document.getElementById('gs-menu'); menu.replaceChildren();
        t.menu.forEach((m) => html('li', null, m, menu));
        const itens = document.getElementById('gs-itens'); itens.replaceChildren();
        t.itens.forEach((i) => { const li = html('li', null, null, itens); html('strong', null, i[0], li); html('span', null, i[1], li); });

        const quadro = document.getElementById('gs-quadro'); quadro.replaceChildren();
        const s = window.UG.svg('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': 1.4, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, quadro);
        window.UG.svg('path', { d: t.desenho }, s);

        document.querySelectorAll('#gs-cores .gs-cor').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.cor === estado.cor)));
        // A prévia é um desenho: quem usa leitor de tela recebe a descrição do que mudou.
        preencher('#gs-descricao', 'Prévia: site de ' + t.rotulo.toLowerCase() + ' "' + nome + '", fundo ' + estado.tema + ', cor da marca ' + estado.cor + (logoUrl ? ', com o seu logo.' : '.'));
    }

    const tipos = document.getElementById('gs-tipo');
    Object.keys(TIPOS).forEach((k) => { const b = html('button', null, TIPOS[k].rotulo, tipos); b.type = 'button'; b.dataset.valor = k; b.setAttribute('aria-pressed', String(k === estado.tipo)); });
    filtro(tipos, (v) => { estado.tipo = v; if (!estado.nomeDigitado) campoNome.value = ''; campoNome.placeholder = TIPOS[v].nome; desenhar(); });
    filtro(document.getElementById('gs-tema'), (v) => { estado.tema = v; desenhar(); });

    const cores = document.getElementById('gs-cores'), livre = document.getElementById('gs-cor-livre');
    CORES.forEach((c) => {
        const b = document.createElement('button');
        b.type = 'button'; b.className = 'gs-cor'; b.dataset.cor = c[1]; b.style.setProperty('--c', c[1]); b.setAttribute('aria-label', c[0]);
        b.addEventListener('click', () => { estado.cor = c[1]; livre.value = c[1]; desenhar(); });
        cores.insertBefore(b, livre);
    });
    livre.addEventListener('input', () => { estado.cor = livre.value; desenhar(); });

    /**
     * CNPJ → nome, cidade e tipo de site, pelos dados públicos da Receita
     * (BrasilAPI, consultada pelo servidor do site). Falhou, nada muda: o
     * visitante segue digitando à mão.
     */
    const cnpjCampo = document.getElementById('gs-cnpj'), cnpjNota = document.getElementById('gs-cnpj-nota');
    const NOTA_CNPJ = cnpjNota.textContent;
    cnpjCampo.addEventListener('input', () => {
        const d = cnpjCampo.value.replace(/\D/g, '').slice(0, 14);
        cnpjCampo.value = d.replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3').replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d)/, '$1-$2');
    });
    document.getElementById('gs-cnpj-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const d = cnpjCampo.value.replace(/\D/g, '');
        if (d.length !== 14) { cnpjNota.textContent = 'O CNPJ tem 14 números.'; return; }
        cnpjNota.textContent = 'Consultando…';
        try {
            const r = await fetch('/demonstracoes/dados/cnpj/' + d, { headers: { Accept: 'application/json' } });
            const j = await r.json();
            if (!r.ok) { cnpjNota.textContent = j.erro || 'Não deu para consultar agora. Preencha à mão.'; return; }
            if (j.nome) { campoNome.value = j.nome; estado.nomeDigitado = true; }
            estado.cidade = j.cidade + (j.uf ? '/' + j.uf : '');
            if (j.tipo && TIPOS[j.tipo]) {
                estado.tipo = j.tipo;
                tipos.querySelectorAll('button').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.valor === j.tipo)));
            }
            cnpjNota.textContent = (j.nome ? j.nome + ', ' : 'Sem nome fantasia (é de pessoa física, não mostramos). ') + estado.cidade + '.' + (j.tipo ? '' : ' Escolha o tipo de site.');
            desenhar();
        } catch (err) {
            cnpjNota.textContent = 'Não deu para consultar agora. Preencha à mão.';
        }
    });
    cnpjCampo.addEventListener('focus', () => { if (cnpjNota.textContent !== NOTA_CNPJ && !cnpjCampo.value) cnpjNota.textContent = NOTA_CNPJ; });

    campoNome.placeholder = TIPOS[estado.tipo].nome;
    campoNome.addEventListener('input', () => { estado.nomeDigitado = campoNome.value.trim() !== ''; desenhar(); });

    function tirarLogo(limparCampo) {
        if (logoUrl) URL.revokeObjectURL(logoUrl);
        if (limparCampo) logoCampo.value = '';
        logoUrl = null; logoImg.hidden = true; logoImg.removeAttribute('src'); logoTirar.hidden = true;
    }
    logoCampo.addEventListener('change', () => {
        const arquivo = logoCampo.files && logoCampo.files[0];
        tirarLogo(false);
        // Só imagem, e só como endereço local do próprio navegador (blob:), dentro de um <img>: SVG ali não roda script.
        if (arquivo && /^image\/(png|jpeg|webp|svg\+xml)$/.test(arquivo.type) && arquivo.size <= 4 * 1024 * 1024) {
            logoUrl = URL.createObjectURL(arquivo); logoImg.src = logoUrl; logoImg.hidden = false; logoTirar.hidden = false;
        } else if (arquivo) {
            logoCampo.value = '';
            desenhar();
            preencher('#gs-descricao', 'Esse arquivo não serve de logo: use PNG, JPG, WebP ou SVG de até 4 MB.');
            return;
        }
        desenhar();
    });
    logoTirar.addEventListener('click', () => { tirarLogo(true); desenhar(); logoCampo.focus(); });

    desenhar();
})();
