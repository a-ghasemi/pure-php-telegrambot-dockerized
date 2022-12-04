<?php

namespace App\Bot\General;

use Illuminate\Support\Facades\Cache;
use Longman\TelegramBot\Commands\Command;

class BotSession
{
    protected Command $command;
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

    protected function getCache(): void
    {
        $cache = Cache::get($this->getSessionId());

        if ($cache) {
            $this->variables = $cache;
        }
    }

    protected function updateCache(): void
    {
        Cache::put($this->session_id, $this->variables);
    }

    protected function getSessionId(): string
    {
        return "sess_" . $this->command->getMessage()?->getChat()?->getId();
    }

    public function __set(string $name, $value): void
    {
        $this->variables[$name] = $value;
        $this->updateCache();
    }

    public function __get(string $name)
    {
        return $this->variables[$name] ?? null;
    }
}
