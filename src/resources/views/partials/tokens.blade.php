{{--
    Camada de tokens do site — o ÚNICO lugar com cor literal.

    Contrato da casa, §0 (socrates/docs/urbis-design/CORE.md): nenhuma cor
    literal fora daqui. Quem precisa de uma cor usa a variável; quem precisa
    de uma cor que não existe discute o token, não inventa o hex.

    Incluído por `layouts/site.blade.php` — uma vez, para o site inteiro.

    ────────────────────────────────────────────────────────────────────────
    OS DOIS TEMAS (23/08/2026)

    Até aqui o site era escuro e ponto, enquanto a página "Como trabalhamos"
    afirma que entregamos "tema claro e escuro". Ou o site passava a ter os
    dois, ou a frase saía. Ele decidiu: passa a ter os dois.

    A marca continua nascendo ESCURA — "cidade à noite" é a identidade, não
    um modo. Por isso o `:root` é o escuro e o claro é a classe `.claro` no
    `<html>`, ao contrário do resto da casa (onde a classe é `.dark`). O
    CORE §2 exige a classe no `<html>`, três estados (claro/escuro/sistema),
    persistência e script síncrono no `<head>` — tudo isso está cumprido; o
    que inverte é qual dos dois é o padrão, e isso é decisão de marca.

    Não há `@media (prefers-color-scheme)` aqui de propósito: quem resolve o
    estado "sistema" é o script do `<head>`, que lê a MESMA chave e aplica a
    MESMA regra. Duas fontes de verdade para o tema é exatamente o defeito
    que o CORE §2 item 3 descreve (foi achado no Sócrates em 03/08).

    ────────────────────────────────────────────────────────────────────────
    NOMES SEMÂNTICOS + APELIDOS ANTIGOS

    Os tokens de verdade são semânticos (`--bg`, `--fg`, `--accent`…), como
    manda o CORE §1. Os nomes antigos (`--ink-900`, `--text-hi`, `--amber-500`)
    viraram APELIDOS que apontam para eles — o CSS das páginas já falava essa
    língua em ~1.100 linhas, e trocar tudo de uma vez era risco sem retorno.

    O apelido também conserta uma armadilha: no tema claro, "amber-400" é mais
    ESCURO que "amber-500", porque em fundo claro o hover escurece em vez de
    clarear. O número no nome vira mentira; o papel (`--accent-hover`) não.
--}}
:root {
    color-scheme: dark;

    /* ---- Superfície — cidade à noite ---- */
    --bg:              #0B1220;
    --surface:         #101A2D;
    --surface-raised:  #16233C;
    --border:          #22304B;
    /* Borda de CAMPO é outra coisa que borda de card (CORE §1): a do card é
       decoração — a diferença entre fundo e card já separa os dois —, mas a
       do campo é o que informa que ali se digita, e a WCAG 1.4.11 pede 3:1.
       O `--border` dá 1,42:1: some. Isto dá 3,29:1 sobre o fundo do campo. */
    --border-input:    #55678A;

    /* Cromo translúcido: header grudado e véu de modal. Saíram de dentro dos
       componentes em 23/08 — eram `rgba()` cru, e no tema claro deixavam uma
       faixa azul-noite atravessada em cima da página. */
    --header-bg:       rgba(11, 18, 32, .82);
    --overlay:         rgba(6, 10, 20, .7);
    --grid-line:       rgba(34, 48, 75, .5);
    --shadow-flutuante: 0 4px 14px rgba(0, 0, 0, .35);
    --shadow-painel:   0 12px 32px rgba(6, 10, 20, .55);

    /* ---- Texto ---- */
    --fg:        #EAF0FA;
    --fg-muted:  #A7B4C9;
    /* Era #6B7A93 e REPROVAVA: 4,31:1 sobre o fundo e 4,00:1 sobre o card,
       abaixo do piso de 4,5:1 do CORE §7 — e este token carrega texto de
       verdade (legenda de seção, rodapé, ajuda de campo), não decoração.
       Medido e corrigido em 23/08: agora 5,14:1 e 4,77:1. */
    --fg-subtle: #78879F;

    /* ---- Marca — âmbar "luz da cidade" ---- */
    --accent:         #F5A524;
    --accent-hover:   #FFC24B;
    --accent-active:  #D18A0F;
    --fg-on-accent:   #1A1204;

    /* ---- Distritos (cores dos produtos — usar SÓ nos cards) ----
       O violeta era #8B5CF6 e o vermelho #E5484D: 4,42:1 e 4,44:1 sobre o
       card, os dois abaixo do piso. Aparecem como TEXTO (a etiqueta do card
       de distrito, o recado de erro do formulário), não como enfeite. Ambos
       clarearam em 23/08 — 6,39:1 e 5,85:1 — que é a regra do CORE para o
       tema escuro, e não uma troca de identidade: mesmo matiz, outro tom. */
    --d-synapse: #2DD4BF;
    --d-urb:     #A78BFA;
    --d-ssb:     #F26A6E;

    /* ---- Semânticas ---- */
    --ok:    #3DD68C;
    --warn:  #F5A524;
    --error: #F26A6E;
}

html.claro {
    color-scheme: light;

    /* "Cidade de dia": o mesmo azul da marca, virado do avesso. O fundo puxa
       para o frio de propósito — branco puro achata a página e apaga a
       separação entre fundo e card, que aqui é o que sustenta a hierarquia. */
    --bg:              #F1F4FA;
    --surface:         #FFFFFF;
    --surface-raised:  #FFFFFF;
    --border:          #D6DEEC;
    --border-input:    #7B8BA6;   /* 3,13:1 sobre o campo, 3,45:1 sobre o card */

    --header-bg:       rgba(241, 244, 250, .86);
    --overlay:         rgba(11, 18, 32, .45);
    --grid-line:       rgba(146, 161, 187, .38);
    --shadow-flutuante: 0 4px 14px rgba(11, 18, 32, .12);
    --shadow-painel:   0 12px 32px rgba(11, 18, 32, .16);

    --fg:        #0B1220;
    --fg-muted:  #45536B;
    --fg-subtle: #5C6B85;

    /* O âmbar da noite (#F5A524) dá 2,1:1 sobre branco — ilegível como texto.
       No claro a marca ESCURECE, que é o espelho da regra do CORE ("a cor da
       marca precisa clarear no escuro"). Medido: 5,20:1 sobre o fundo e
       5,73:1 sobre o card. E como o botão primário fica escuro, o texto em
       cima dele vira claro — o `--fg-on-accent` vira junto, senão o botão
       principal reprova. */
    --accent:         #96570A;
    --accent-hover:   #7A4708;
    --accent-active:  #5F3706;
    --fg-on-accent:   #FFF8EC;

    /* Os semânticos também escurecem: verde e vermelho de tema escuro sobre
       branco ficam abaixo de 4,5:1. */
    --ok:    #17784A;
    --warn:  #8A5A00;
    --error: #C0272C;

    --d-synapse: #0F766E;
    --d-urb:     #6D28D9;
    --d-ssb:     #C0272C;
}

/* ---- Marcas de terceiros ----------------------------------------------
   Cores oficiais de WhatsApp, Instagram e LinkedIn. Não são da casa e não
   mudam com o tema — mas moram aqui pelo mesmo motivo que todo o resto:
   componente nenhum escreve hex. */
:root {
    --marca-whatsapp: #25d366;
    --marca-linkedin: #0a66c2;
    --marca-instagram: radial-gradient(circle at 30% 110%, #fdf497 0%, #fd5949 45%, #d6249f 60%, #285aeb 90%);
    --marca-sobre:    #fff;
}

/* ---- Apelidos do CSS antigo (ver o cabeçalho deste arquivo) ---- */
:root, html.claro {
    --ink-900:   var(--bg);
    --ink-800:   var(--surface);
    --ink-700:   var(--surface-raised);
    --line:      var(--border);
    --line-input: var(--border-input);
    --text-hi:   var(--fg);
    --text-mid:  var(--fg-muted);
    --text-low:  var(--fg-subtle);
    --amber-400: var(--accent-hover);
    --amber-500: var(--accent);
    --amber-600: var(--accent-active);
    --amber-ink: var(--fg-on-accent);
}

/* ---- Forma e tipografia (não mudam com o tema) ---- */
:root {
    --r-btn: 10px;
    --r-card: 16px;
    --font-display: "Bricolage Grotesque", system-ui, sans-serif;
    --font-body: "Instrument Sans", system-ui, sans-serif;
    --font-mono: "JetBrains Mono", ui-monospace, monospace;
}
