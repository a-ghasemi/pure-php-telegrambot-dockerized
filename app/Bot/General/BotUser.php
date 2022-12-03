<?php

namespace App\Bot\General;

use App\Models\TelegramId;
use App\Models\User;
use Longman\TelegramBot\Commands\Command;

class BotUser
{
    protected Command $command;

    public function __construct($command)
    {
        $this->command = $command;
    }

    public function getRegisteredUser():User|null
    {
        $user_id = $this->command->getMessage()?->getFrom()?->getId();
        if(!$user_id) return null;

        $telegram_id = TelegramId::where('telegram_id',$user_id)->first();

        if(!$telegram_id) return null;
        return $telegram_id->user();
    }
}
