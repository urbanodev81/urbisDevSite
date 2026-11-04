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

    // "Comentar com o LinkedIn" no blog (bloco 3b da 246, 04/11/2026). Sign In
    // with LinkedIn (OpenID Connect), escopos openid/profile/email. Sem as duas
    // chaves, o botão nem aparece e as rotas dão 404: comentar por nome +
    // e-mail continua valendo. O app é criado pelo Ricardo (docs SETUP).
    'linkedin-openid' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('LINKEDIN_REDIRECT_URI', '/blog/entrar/linkedin/retorno'),
    ],
];
