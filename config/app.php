<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Loja de Carrinhos'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim((string) env('APP_URL', 'http://localhost'), '/'),
    'timezone' => env('APP_TIMEZONE', 'America/Sao_Paulo'),

    'session' => [
        'name' => env('SESSION_NAME', 'loja_carrinhos_session'),
        'secure' => filter_var(env('SESSION_SECURE_COOKIE', false), FILTER_VALIDATE_BOOLEAN),
    ],

    'install_key' => env('INSTALL_KEY', ''),
];
