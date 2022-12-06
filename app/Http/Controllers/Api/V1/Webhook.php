<?php

namespace App\Http\Controllers\Api\V1;

use App\Bot\RobotHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Longman\TelegramBot\Exception\TelegramException;
use Packages\BotConfig;

class Webhook extends Controller
{
    public function webhook(Request $request, string $token): \Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
//        Bot::where('token',$token)->firstOrFail();

        abort_unless(trim($token) == config('bot.webhook.token'),404);

        $bot_config = new BotConfig();
        try {
            $bot_config->checkConfiguration();
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }

        new RobotHandler($request, $bot_config);

        return response('done',200);

    }

}
