<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Longman\TelegramBot\Exception\TelegramException;
use Packages\BotConfig;

class BotGetWebhookInfo extends Command
{
    protected $signature = 'bot:webhook:info';

    protected $description = 'Gets webhook info for the bot';

    public function handle()
    {
        $bot_config = new BotConfig();
        try {
            $bot_config->checkConfiguration();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        try {
            $telegram = $bot_config->makeTelegramInstance();
//            $result = $telegram->getInfo();

//            if ($result->isOk()) {
//                $this->comment($result->getDescription());
//            }
        } catch (TelegramException $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

}
