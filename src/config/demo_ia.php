<?php

// A demonstração "Pergunte aos dados" (pendência 203, item 10). A IA, a chave
// dela, o lead e a contagem moram no Sócrates; o site só leva a pergunta, com
// o token de projeto dele. Sem os dois valores abaixo a demonstração não
// existe: some da lista e o endereço responde 404.
return [

    // Ex.: https://socrates.urbisdev.tech/api/demo-site
    'url' => env('DEMO_IA_URL'),

    // Token de API do ambiente do projeto do site no Sócrates.
    'token' => env('DEMO_IA_TOKEN'),

];
