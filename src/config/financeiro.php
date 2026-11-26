<?php

/*
 * Financeiro do painel — pacote urbanodev/financeiro (desde 26/11/2026).
 * README do pacote: vendor/urbanodev/financeiro/README.md.
 */
return [
    // As tabelas já existem (migration 2026_11_26_100000_cria_financeiro, de
    // antes do pacote, com as mesmas colunas). Não carregue as do pacote.
    'carregar_migrations' => false,

    'tabelas' => [
        'compromissos' => 'compromissos',
        'pagamentos' => 'compromisso_pagamentos',
    ],

    // Os do app estendem os do pacote e põem a trilha de auditoria.
    'modelos' => [
        'compromisso' => App\Models\Compromisso::class,
        'pagamento' => App\Models\PagamentoDeCompromisso::class,
    ],

    'tipos' => [
        'hospedagem' => 'Hospedagem',
        'dominio' => 'Domínio',
        'mensalidade' => 'Mensalidade de cliente',
        'lembrete' => 'Lembrete',
        'outro' => 'Outro',
    ],

    'avisos' => [
        // outbox → Sócrates → Telegram
        'avisador' => App\Support\Financeiro\AvisadorDoSite::class,
        'repetir_atraso_a_cada_dias' => 7,
    ],

    // PIX da cobrança de mensalidade (QR + copia e cola na tela). Sem as três,
    // a tela não mostra PIX. Conta decidida em 26/11/2026: telefone dele.
    'pix' => [
        'chave' => env('FINANCEIRO_PIX_CHAVE'),
        'nome' => env('FINANCEIRO_PIX_NOME'),
        'cidade' => env('FINANCEIRO_PIX_CIDADE'),
    ],
];
