<?php

return [
    'telegram' => [
        'token'    => env('BOT_TOKEN', ''),
        'username' => env('BOT_USERNAME', ''),
    ],

    'webhook' => [
        'token' => env('BOT_WEBHOOK_TOKEN', ''),
    ],
];
