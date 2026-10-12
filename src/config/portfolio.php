<?php

// Os trabalhos que o site mostra vêm do Sócrates (pendência 203): cada projeto
// de lá diz se aparece, em que categoria e com que nome. O site só LÊ, guarda
// uma cópia e segue no ar com o Sócrates fora (ver App\Support\Portfolio).
return [
    // Vazio = o site não tem portfólio: a seção da home, a página e o link do
    // menu somem. Ex.: https://socrates.urbisdev.tech/api/portfolio
    'url' => env('PORTFOLIO_URL'),

    // De quanto em quanto tempo o site volta a perguntar.
    'minutos' => (int) env('PORTFOLIO_MINUTOS', 30),

    // Com o Sócrates fora, quanto esperar antes de tentar de novo. Sem isto,
    // cada visita pagaria o tempo de espera da tentativa.
    'minutos_apos_falha' => 5,
];
