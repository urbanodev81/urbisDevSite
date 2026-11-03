<?php

// O Turnstile saiu em 29/09/2026 — o captcha do contato é o Altcha
// auto-hospedado (App\Support\Captcha\Altcha), que não tem chave de
// terceiro: assina com uma derivação do APP_KEY.
return [
    'captcha' => [
        // Smoke de login pós-deploy (03/11/2026): só vale fora de produção.
        // Ver App\Rules\Captcha.
        'qa_bypass_token' => env('CAPTCHA_QA_BYPASS_TOKEN'),
    ],
];
