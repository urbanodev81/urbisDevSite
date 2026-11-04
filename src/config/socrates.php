<?php

// O que o site CONTA ao Sócrates pela outbox (04/11/2026, bloco 2 da 246).
// É outra conversa que a do portfólio (o site LÊ, sem token) e a da
// demonstração de IA (`config/demo_ia.php`). O token pode ser o mesmo da
// demonstração: é o token de API do ambiente do projeto do site no Sócrates.
return [

    // Ex.: https://socrates.urbisdev.tech (sem /api no fim)
    'url' => env('SOCRATES_URL'),

    'token' => env('SOCRATES_TOKEN'),

    // `lead.recebido` só liga quando o Sócrates aceitar a chave no endpoint de
    // lá (regra do molde: tipo novo só depois do outro lado). Até lá o lead
    // fica no painel daqui e no e-mail, e nada vai para a outbox.
    'leads' => (bool) env('SOCRATES_LEADS', false),

];
