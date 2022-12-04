<?php

namespace App\Bot\General;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Longman\TelegramBot\Commands\Command;

class BotSession
{
    protected Command $command;
    protected array $variables = [];

    public function __construct($command)
    {
        $this->command = $command;
    }

    protected function getCache(): void
    {
        $cache = Cache::get($this->getSessionId());
        $this->variables = $cache ?? [];

Log::debug('get_cache:'.$this->getSessionId(). PHP_EOL . var_export($this->variables,true));
    }

    protected function updateCache(): void
    {
        Cache::put($this->getSessionId(), $this->variables);

Log::debug('update_cache:'.$this->getSessionId() . PHP_EOL . var_export($this->variables,true));
    }

    protected function getSessionId(): string
    {
        return "sess_" . $this->command->getMessage()?->getChat()?->getId();
    }

    protected function __set(string $name, $value): void
    {
        $this->getCache();
        $this->variables[$name] = $value;
        $this->updateCache();
    }

    protected function __get(string $name)
    {
        $this->getCache();
        return $this->variables[$name] ?? null;
    }
}
