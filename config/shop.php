<?php

return [
    // Вход через Google. Значения берутся из файла .env (см. README)
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    ],
];
