<?php

return [
    // Новая Почта: бесплатный ключ из бизнес-кабинета (для выбора города и отделения)
    'novaposhta' => [
        'api_key' => env('NOVAPOSHTA_API_KEY'),
    ],

    // Вход через Google. Значения берутся из файла .env (см. README)
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    ],
];
