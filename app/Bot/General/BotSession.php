<?php

namespace App\Bot\General;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Longman\TelegramBot\Commands\Command;

class BotSession
{
    protected Command $command;
    protected string $chat_id;
    protected array $variables = [];

    public function __construct($command)
    {
        $this->command = $command;
        $this->chat_id = $command->getMessage()?->getChat()?->getId() ?? 'no_id'.Str::random(10);
    }

    public function refresh($force = false)
    {
        $command = $this->getCurrCommand();
        $this->variables = [];
        if($command && !$force) $this->setCurrCommand($command);
        $this->updateCache();
    }

    protected function getCache(): void
    {
        $cache = Cache::get($this->getSessionId());
        $this->variables = $cache ?? [];

//Log::debug('get_cache:'.$this->getSessionId(). PHP_EOL . var_export($this->variables,true));
    }

    protected function updateCache(): void
    {
        Cache::put($this->getSessionId(), $this->variables);

//Log::debug('update_cache:'.$this->getSessionId() . PHP_EOL . var_export($this->variables,true));
    }

    protected function getSessionId(): string
    {
        $sess_id = "sess_" . $this->chat_id;
        return $sess_id;
    }

    public function __set(string $name, $value): void
    {
        $this->getCache();
        $this->variables[$name] = $value;
        $this->updateCache();
    }

    public function __get(string $name)
    {
        $this->getCache();
        return $this->variables[$name] ?? null;
    }

    public function setCurrCommand($command): void
    {
        $this->variables['executed_command'] = $command;
        $this->updateCache();
    }

    public function getCurrCommand(): ?string
    {
        return $this->variables['executed_command'] ?? null;
    }
}
