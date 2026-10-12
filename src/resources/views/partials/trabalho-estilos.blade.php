{{-- O cartão de trabalho do portfólio: usado na home e em /trabalhos. --}}
<style>
    .sr-only { position: absolute; width: 1px; height: 1px; margin: -1px; padding: 0; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0; }
    .trabalhos { display: grid; gap: 20px; }
    @media (min-width: 720px) { .trabalhos { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1080px) { .trabalhos { grid-template-columns: repeat(3, 1fr); } }
    .trabalho { display: flex; flex-direction: column; background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-card); overflow: hidden; color: var(--fg-muted); }
    .trabalho-tela { aspect-ratio: 16 / 10; background: var(--bg); border-bottom: 1px solid var(--border); display: grid; place-items: center; overflow: hidden; }
    .trabalho-tela img { width: 100%; height: 100%; object-fit: cover; object-position: top center; display: block; }
    .trabalho-tela span { font-family: var(--font-mono); font-size: .72rem; letter-spacing: .14em; text-transform: uppercase; color: var(--fg-subtle); padding: 16px; text-align: center; }
    .trabalho-corpo { display: flex; flex-direction: column; gap: 8px; padding: 20px 22px 22px; flex: 1; }
    .trabalho-estado { align-self: flex-start; font-family: var(--font-mono); font-size: .7rem; letter-spacing: .12em; text-transform: uppercase; color: var(--fg-subtle); display: inline-flex; align-items: center; gap: 8px; }
    .trabalho-estado::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: var(--fg-subtle); flex: none; }
    .trabalho-estado[data-estado="no-ar"]::before { background: var(--accent); }
    .trabalho-titulo { font-family: var(--font-display); font-size: 1.25rem; font-weight: 700; color: var(--fg); }
    .trabalho-tipo { font-size: .9rem; color: var(--fg-subtle); }
    .trabalho-corpo p { font-size: .96rem; line-height: 1.6; }
    .trabalho-visitar { margin-top: auto; padding-top: 8px; font-weight: 600; color: var(--accent); text-decoration: none; min-height: 44px; display: inline-flex; align-items: center; align-self: flex-start; }
    .trabalho-visitar:hover { text-decoration: underline; }
</style>
