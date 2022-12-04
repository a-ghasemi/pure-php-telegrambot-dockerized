<?php
namespace App\Bot\General;

use App\Models\User;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;

abstract class ExtendedSystemCommand extends SystemCommand
{
    protected User|null $user;
    protected BotSession $session;
    protected $debug = false;

    public function __construct(Telegram $telegram, ?Update $update = null)
    {
        parent::__construct($telegram, $update);

        $this->user = (new BotUser($this))->getRegisteredUser();
        $this->session = (new BotSession($this));
    }

    protected function debugLog(string $message): void
    {
        if (!$this->debug) return;

        $username = $this->getMessage()?->getFrom()?->getUsername();
        $username = $username ?: '---';

        $command = $this->session->executed_command;
        $command = $command ?: '---';

        $text = "username: {$username}" . PHP_EOL;
        $text .= "command: {$command}" . PHP_EOL;
        $text .= $message;

        $data = [
            'chat_id' => config('bot.telegram.admin_id'),
            'text'    => $text,
//            'reply_markup' => Keyboard::remove(['selective' => true]),
        ];

        Request::sendMessage($data);
    }

}
