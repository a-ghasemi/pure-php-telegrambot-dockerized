<?php

return [
    'telegram' => [
        'token'    => env('BOT_TOKEN', ''),
        'username' => env('BOT_USERNAME', ''),
        'admin_id' => env('BOT_ADMIN_ID', ''),
    ],

    'webhook' => [
        'token' => env('BOT_WEBHOOK_TOKEN', ''),
    ],
];
