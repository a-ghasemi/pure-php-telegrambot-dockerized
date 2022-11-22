<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Webhook extends Controller
{
    public function webhook(string $token):void
    {
//        Bot::where('token',$token)->firstOrFail();
    }
}
