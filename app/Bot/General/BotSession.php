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

    public function refresh()
    {
        Log::debug(var_export( ['ref_bef_sess', $this->variables],true));
//        $command = $this->variables['executed_command'] ?? null;
        $this->variables = [];
//        if($command && !$force) $this->variables['executed_command'] = $command;
        Log::debug(var_export( ['ref_aft_sess', $this->variables],true));
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
        Log::debug(var_export( ['upd_sess', $this->variables],true));
        Cache::put($this->getSessionId(), $this->variables);

//Log::debug('update_cache:'.$this->getSessionId() . PHP_EOL . var_export($this->variables,true));
    }

    protected function getSessionId(): string
    {
        return "sess_" . $this->command->getMessage()?->getChat()?->getId();
    }

    public function __set(string $name, $value): void
    {
//        Log::debug(var_export( ['set_sess', $this->variables],true));
        $this->getCache();
        $this->variables[$name] = $value;
        $this->updateCache();
    }

    public function __get(string $name)
    {
//        Log::debug(var_export( ['get_sess', $this->variables],true));
        $this->getCache();
        return $this->variables[$name] ?? null;
    }
}
