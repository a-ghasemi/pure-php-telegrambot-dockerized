<?php

return [
    'telegram' => [
        'token'    => env('BOT_TOKEN', ''),
        'username' => env('BOT_USERNAME', ''),
        'admin_id' => env('BOT_ADMIN_ID', ''),
        'debug_mode' => env('BOT_DEBUG_MODE', ''),
    ],

    'webhook' => [
        'token' => env('BOT_WEBHOOK_TOKEN', ''),
    ],
];
