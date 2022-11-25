<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('interface.home');
});

Route::get('webhook/{token}',"\App\Http\Controllers\Webhook@webhook");
