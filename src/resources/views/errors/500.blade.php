{{--
    A 500 do site (18/10/2026). Antes saía a tela padrão do Laravel, "500 |
    Server Error" sem marca — vista de verdade no envio do contato.

    DE PROPÓSITO NÃO ESTENDE `layouts.site`: o casco consulta o portfólio e
    inclui partials; se o defeito estiver em algum deles, a página de erro
    quebraria junto e voltaria a tela crua. Aqui só entram os tokens (CSS
    estático) e nada que toque banco, sessão ou serviço.
--}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <script>
        (function () {
            var claro = false;
            try {
                var t = localStorage.getItem('urbis-tema');
                claro = t ? t === 'claro'
                          : window.matchMedia('(prefers-color-scheme: light)').matches;
            } catch (e) { /* storage bloqueado: fica no escuro */ }
            document.documentElement.classList.toggle('claro', claro);
        })();
    </script>
    <title>Algo deu errado — UrbisDev</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=bricolage-grotesque:700|instrument-sans:400,600|jetbrains-mono:400&display=swap" rel="stylesheet">
    <style>
        @include('partials.tokens')
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { min-height: 100vh; display: grid; align-items: center; background: var(--bg); color: var(--fg-muted); font-family: var(--font-body); font-size: 1.0625rem; line-height: 1.7; -webkit-font-smoothing: antialiased; }
        .container { max-width: 1160px; width: 100%; margin-inline: auto; padding: clamp(88px, 12vw, 150px) 24px; }
        @media (min-width: 768px) { .container { padding-inline: 32px; } }
        .eyebrow { display: inline-flex; align-items: center; gap: 10px; font-family: var(--font-mono); font-size: .75rem; letter-spacing: .14em; text-transform: uppercase; color: var(--fg-subtle); }
        .eyebrow::before { content: ""; width: 6px; height: 6px; background: var(--accent); flex: none; }
        h1 { margin: 16px 0 18px; font-family: var(--font-display); color: var(--fg); line-height: 1.08; letter-spacing: -0.02em; font-size: clamp(2.2rem, 5vw, 3.4rem); }
        p { max-width: 52ch; font-size: 1.1rem; }
        .saidas { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 32px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1rem; padding: 12px 22px; min-height: 44px; border-radius: var(--r-btn); text-decoration: none; border: 1px solid transparent; font-family: var(--font-body); }
        .btn-primary { background: var(--accent); color: var(--fg-on-accent); }
        .btn-primary:hover { background: var(--accent-hover); }
        .btn-ghost { background: transparent; border-color: var(--border); color: var(--fg); }
        .btn-ghost:hover { border-color: var(--accent); }
        .btn:focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }
    </style>
</head>
<body>
    <main class="container" aria-labelledby="erro-titulo">
        <span class="eyebrow">Erro 500</span>
        <h1 id="erro-titulo">Algo deu errado do nosso lado.</h1>
        <p>O problema não foi com você, e já ficou registrado para a gente corrigir. Tente de novo em alguns minutos — ou fale direto com a gente por e-mail.</p>
        <div class="saidas">
            <a class="btn btn-primary" href="/">Ir para o início</a>
            <a class="btn btn-ghost" href="mailto:contato@urbisdev.tech">contato@urbisdev.tech</a>
        </div>
    </main>
</body>
</html>
