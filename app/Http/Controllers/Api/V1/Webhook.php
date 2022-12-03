<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Longman\TelegramBot\Exception\TelegramException;
use Packages\BotConfig;

class Webhook extends Controller
{
    public function webhook(string $token): void
    {
//        Bot::where('token',$token)->firstOrFail();

        abort_unless(trim($token) == config('bot.webhook.token'),404);

        $bot_config = new BotConfig();
        try {
            $bot_config->checkConfiguration();
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }

        try {
            $telegram = $bot_config->makeTelegramInstance();
            $telegram->addCommandsPath(app_path('Bot/Commands'));
            $telegram->handle();
        } catch (TelegramException $e) {
            error_log($e->getMessage());
        }
    }

}
