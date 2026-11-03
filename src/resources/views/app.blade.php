{{--
    Página raiz do PAINEL (Inertia) — 03/11/2026, pendência 246.

    O site público NÃO usa este arquivo: ele estende `layouts/site.blade.php`.

    CSP: a política do site barra script embutido sem nonce
    (`SecurityHeaders`). Por isso o tema, o `@routes` do Ziggy e as tags do
    `@vite` levam o nonce da requisição. O dado da página o Inertia escreve
    num `<script type="application/json">`, que não executa.
--}}
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="application-name" content="{{ config('marca.nome') }}">
        <meta name="robots" content="noindex, nofollow">

        <title data-inertia>{{ config('marca.nome') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">

        {{--
            Tema antes da primeira pintura, com a MESMA chave e a MESMA regra
            do site (`urbis-tema`: 'claro' | 'escuro' | nada = sistema). A
            marca nasce escura. Par do `Composables/useTheme.js`.
        --}}
        <script nonce="{{ $cspNonce ?? '' }}">
            (function () {
                var claro = false;
                try {
                    var t = localStorage.getItem('urbis-tema');
                    claro = t ? t === 'claro'
                              : window.matchMedia('(prefers-color-scheme: light)').matches;
                } catch (e) {}
                document.documentElement.classList.toggle('claro', claro);
                document.documentElement.classList.toggle('dark', !claro);
            })();
        </script>

        <link rel="stylesheet" href="/fonts/fonts.css">

        {{-- O captcha do login: o mesmo Altcha auto-hospedado do contato. --}}
        <script type="module" src="/vendor/altcha/pt-br-3.2.2.js"></script>
        <script type="module" src="/vendor/altcha/altcha-3.2.2.min.js"></script>

        @routes(nonce: $cspNonce ?? null)
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="bg-bg font-sans text-fg antialiased">
        @inertia
    </body>
</html>
