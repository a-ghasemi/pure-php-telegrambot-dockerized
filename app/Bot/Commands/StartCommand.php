<?php
namespace App\Bot\Commands;

use App\Bot\General\BotUser;
use App\Bot\General\ExtendedSystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;

class StartCommand extends ExtendedSystemCommand
{
    protected $name = 'start';
    protected $description = 'Start command';
    protected $usage = '/start';

    public function execute(): ServerResponse
    {
        if($this->user){
            return $this->showUserMenu();
        }

        return $this->replyToChat( __('bot.question.create') );
    }

    protected function showUserMenu()
    {
        return $this->replyToChat( __('bot.user_menu.welcome') );
    }

}
