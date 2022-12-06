<?php

namespace App\Http\Controllers\Api\V1;

use App\Bot\General\BotConfig;
use App\Bot\MigrantRobot;
use App\Http\Controllers\Controller;
use App\Models\BotConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Webhook extends Controller
{
    public function index(Request $request, string $token)
    {
        $bot = BotConnection::where('active', true)->where('webhook_token', $token)->first();

        if (!$bot) {
            mylog('Wrong Bot Token', 'Warning', "Encoded Bot Token: " . base64_encode($token));
            return response('done', 200);
        }

        mylog($bot->title . ' Bot Hooked', 'Info',
            "Bot Name: " . $bot->title . '<br/>' .
            "Bot Username:  " . $bot->username . '<br/>' .
            "Bot Classname: " . Str::ucfirst(Str::camel($bot->username)) . '<br/>' .
            "Bot Token: " . $token);

        $robotClass = 'App\\Bot\\' . Str::ucfirst(Str::camel($bot->username));
        new $robotClass($request, $bot);

        return response('done', 200);
    }
}
