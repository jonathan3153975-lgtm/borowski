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

    'uploads' => [
        'base_path' => BASE_PATH . '/storage/uploads',
        'loja_itens' => [
            'path' => BASE_PATH . '/storage/uploads/loja_itens',
            'max_size' => (int) env('ITEM_IMAGE_MAX_SIZE', 5242880),
            'max_images' => (int) env('ITEM_IMAGE_MAX_IMAGES', 6),
            'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        ],
    ],
];
