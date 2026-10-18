<?php

return [
    'default' => env('LOG_CHANNEL', 'stack'),

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        // Relatos da CSP em report-only (`CspRelatoController`), longe do
        // laravel.log: são do navegador do visitante, não erro do site.
        'csp' => [
            'driver' => 'daily',
            'path' => storage_path('logs/csp.log'),
            'level' => 'info',
            'days' => 14,
            'replace_placeholders' => true,
        ],
    ],
];
