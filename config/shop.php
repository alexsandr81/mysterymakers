<?php

return [
    // Новая Почта: бесплатный ключ из бизнес-кабинета (для выбора города и отделения)
    'novaposhta' => [
        'api_key' => env('NOVAPOSHTA_API_KEY'),
    ],

    // Google Диск: папки категорий и товаров (см. README, раздел 5.4)
    'drive' => [
        'root_id' => env('GOOGLE_DRIVE_ROOT_ID'),
        'key_file' => env('GOOGLE_DRIVE_KEY_FILE', storage_path('app/google-drive.json')),
    ],

    // Вход через Google. Значения берутся из файла .env (см. README)
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    ],
];
