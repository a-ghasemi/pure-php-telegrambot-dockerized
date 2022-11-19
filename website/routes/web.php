<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('webhook/{token}',"\App\Http\Controllers\Webhook@webhook");
