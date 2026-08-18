{{--
    Camada de tokens do site — o ÚNICO lugar com cor literal.

    Contrato da casa, §0 (socrates/docs/urbis-design/CORE.md): nenhuma cor
    literal fora daqui. Quem precisa de uma cor usa a variável; quem precisa
    de uma cor que não existe discute o token, não inventa o hex.

    Incluído por welcome.blade.php e como-trabalhamos.blade.php.
--}}
:root {
    /* Base — cidade à noite */
    --ink-900: #0B1220;
    --ink-800: #101A2D;
    --ink-700: #16233C;
    --line:    #22304B;

    /* Texto */
    --text-hi:  #EAF0FA;
    --text-mid: #A7B4C9;
    --text-low: #6B7A93;

    /* Acento da marca — âmbar "luz da cidade" */
    --amber-400: #FFC24B;
    --amber-500: #F5A524;
    --amber-600: #D18A0F;
    --amber-ink: #1A1204;

    /* Distritos (cores dos produtos — usar SÓ nos cards) */
    --d-synapse: #2DD4BF;
    --d-urb:     #8B5CF6;
    --d-ssb:     #E5484D;

    /* Semânticas */
    --ok:    #3DD68C;
    --warn:  #F5A524;
    --error: #E5484D;

    --r-btn: 10px;
    --r-card: 16px;
    --font-display: "Bricolage Grotesque", system-ui, sans-serif;
    --font-body: "Instrument Sans", system-ui, sans-serif;
    --font-mono: "JetBrains Mono", ui-monospace, monospace;
}
