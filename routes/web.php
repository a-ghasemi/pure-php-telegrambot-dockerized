<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('interface.home');
});

//Route::get('phpinf/124', function () {
//    phpinfo();
//});

