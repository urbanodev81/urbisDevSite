{{-- Estilo de formulário do site: campos, consentimento e o widget do captcha.
     Mora aqui desde que a demonstração da planilha ganhou formulário próprio
     (11/10/2026); antes vivia só na home. Vai DENTRO de um <style>. --}}
        form { display: grid; gap: 18px; }
        .field { display: grid; gap: 7px; }
        .field label { font-size: .9rem; font-weight: 600; color: var(--text-hi); }
        /* `--border-input`, não `--line`: ver o comentário do token. A borda
           tênue do card não serve para dizer onde se digita. */
        .field input, .field textarea { background: var(--ink-900); border: 1px solid var(--border-input); border-radius: var(--r-btn); padding: 12px 14px; color: var(--text-hi); font: inherit; font-size: 1rem; transition: border-color .16s ease-out; width: 100%; }
        .field input::placeholder, .field textarea::placeholder { color: var(--text-low); }
        .field input:focus, .field textarea:focus { outline: none; border-color: var(--amber-500); }
        .field textarea { min-height: 130px; resize: vertical; }
        .field-row { display: grid; gap: 18px; }
        @media (min-width: 560px) { .field-row { grid-template-columns: 1fr 1fr; } }
        .field-error { color: var(--error); font-size: .82rem; }
        /* O widget do captcha veste os tokens do site — no escuro e no claro,
           porque as variáveis acompanham a classe `.claro` sozinhas. */
        altcha-widget {
            --altcha-color-base: var(--surface);
            --altcha-color-base-content: var(--fg);
            --altcha-border-color: var(--border-input);
            --altcha-border-radius: var(--r-btn);
            --altcha-color-primary: var(--accent);
            --altcha-color-primary-content: var(--fg-on-accent);
            --altcha-color-success: var(--ok);
            --altcha-color-error: var(--error);
            --altcha-max-width: 100%;
        }
        .consent { display: flex; gap: 10px; align-items: center; min-height: 44px; justify-content: flex-start; font-size: .88rem; color: var(--text-low); text-align: left; }
        /* A caixinha desenhada tem 20px porque 44px de checkbox fica grotesco
           no meio do texto — mas a ÁREA que responde ao toque tem 44px, pelo
           `::before` invisível. O alvo é o que a WCAG mede, não o desenho. */
        .consent input[type="checkbox"] { position: relative; width: 20px; height: 20px; flex: none; padding: 0; accent-color: var(--accent); cursor: pointer; }
        .consent input[type="checkbox"]::before { content: ""; position: absolute; inset: -12px; }
        .consent label { display: flex; align-items: center; min-height: 44px; font-weight: 400; font-size: .88rem; color: var(--fg-subtle); cursor: pointer; }
