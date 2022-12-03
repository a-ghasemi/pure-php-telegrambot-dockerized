<?php

namespace App\Bot\General;

use App\Models\TelegramId;
use App\Models\User;
use Longman\TelegramBot\Commands\SystemCommand;

class BotUser
{
    protected SystemCommand $command;

    public function __construct($command)
    {
        $this->command = $command;
    }

    public function getRegisteredUser():User|null
    {
        $user_id = $this->command->getMessage()->getFrom()->getId();
        $telegram_id = TelegramId::where('telegram_id',$user_id)->first();

        if(!$telegram_id) return null;
        return $telegram_id->user();
    }
}
