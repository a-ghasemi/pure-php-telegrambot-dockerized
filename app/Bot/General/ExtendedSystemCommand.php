<?php
namespace App\Bot\General;

use App\Models\User;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Telegram;

abstract class ExtendedSystemCommand extends SystemCommand
{
    protected User $user;
    protected BotSession $session;

    public function __construct(Telegram $telegram, ?Update $update = null)
    {
        $this->user = (new BotUser($this))->getRegisteredUser();
        $this->session = (new BotSession($this));

        parent::__construct($telegram, $update);
    }
}
