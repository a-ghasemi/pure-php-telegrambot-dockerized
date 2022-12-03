<?php

namespace App\Bot\General;

use App\Models\User;
use Longman\TelegramBot\Commands\SystemCommand;

class BotSession
{
    protected SystemCommand $command;
    protected array $variables = [];

    public function __construct($command)
    {
        $this->command = $command;
        $this->getCache();
    }

    public function __destruct()
    {
        $this->updateCache();
    }

    protected function getCache():void
    {
        $cache = cache($this->getSessionId()) ?? null;

        if($cache){
            $this->variables = $cache;
        }
    }

    protected function updateCache():void
    {
        cache([$this->session_id => $this->variables]);
    }

    protected function getSessionId():string
    {
        return "sess_" . $this->command->getMessage()->getChat()->getId();
    }

    public function __set(string $name, $value): void
    {
        $this->variables[$name] = $value;
    }

    public function __get(string $name)
    {
        return $this->variables[$name] ?? null;
    }
}
