<?php

return [
    'turnstile' => [
        'secret_key' => env('TURNSTILE_SECRET', ''),
        'site_key' => env('TURNSTILE_SITE_KEY', '0x4AAAAAADvIu0VwIqcVifXe'),
    ],
];
