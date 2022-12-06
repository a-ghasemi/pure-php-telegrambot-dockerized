<?php

namespace App\Bot\General;

use Longman\TelegramBot\Telegram;

class BotConfig
{
    private string $bot_token;
    private string $bot_username;
    private string $webhook_url;
    public int $id = 1; #TODO: useless attr! remove this after updating old bot classes

    public function __construct()
    {
        $this->bot_token = config('bot.telegram.token');
        $this->bot_username = config('bot.telegram.username');
        $this->webhook_url = config('bot.webhook.token');
    }

    public function checkConfiguration(): void
    {
        if(!$this->bot_token) throw new \Exception('Empty bot token found!');
        if(!$this->bot_username) throw new \Exception('Empty bot username found!');
        if(!$this->webhook_url) throw new \Exception('Empty webhook token found!');
    }

    public function makeTelegramInstance(): Telegram{
        return new Telegram($this->bot_token, $this->bot_username);
    }

    public function getWebhookUrl():string{
        return route('v1.bot.webhook', ['token' => $this->webhook_url]);
    }
}
