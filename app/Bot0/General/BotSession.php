<?php

namespace App\Bot\General;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

    public function refresh()
    {
        Log::debug(var_export( ['ref_bef_sess', $this->variables],true));
//        $command = $this->getCurrCommand();
        $this->variables = [];
//        if($command && !$force) $this->setCurrCommand($command);
        Log::debug(var_export( ['ref_aft_sess', $this->variables],true));
        $this->updateCache();
    }

    protected function getCache(): void
    {
        $cache = Session::get($this->getSessionId());
        $this->variables = $cache ?? [];

//Log::debug('get_cache:'.$this->getSessionId(). PHP_EOL . var_export($this->variables,true));
    }

    protected function updateCache(): void
    {
        Log::debug(var_export( ['upd_sess', $this->variables],true));
        Session::put($this->getSessionId(), $this->variables);

//Log::debug('update_cache:'.$this->getSessionId() . PHP_EOL . var_export($this->variables,true));
    }

    protected function getSessionId(): string
    {
        $sess_id = "sess2_" . $this->chat_id;
Log::debug('sess_ID:'.$sess_id);
        return $sess_id;
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

    public function setCurrCommand($command): void
    {
        $this->variables['executed_command'] = $command;
Log::debug(var_export( ['set_curr_com', $this->variables],true));
        $this->updateCache();
    }

    public function getCurrCommand(): ?string
    {
        return $this->variables['executed_command'] ?? null;
    }
}
