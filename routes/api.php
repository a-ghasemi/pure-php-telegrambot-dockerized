<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group([
        'prefix' => 'v1',
        'as' => 'v1.',
    ], function () {
        Route::get('webhook/{token}', "\App\Http\Controllers\Webhook@webhook")->name('bot.webhook');
});
