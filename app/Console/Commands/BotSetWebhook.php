<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Longman\TelegramBot\Exception\TelegramException;
use Packages\BotConfig;

class BotSetWebhook extends Command
{
    protected $signature = 'bot:webhook:set {--d|dry}';
    protected $description = 'Sets webhook for the bot';
    protected $dry = false;

    public function handle()
    {
        $this->dry = $this->option('dry');

        $bot_config = new BotConfig();
        try {
            $bot_config->checkConfiguration();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        try {
            $telegram = $bot_config->makeTelegramInstance();
            if($this->dry){
                $this->comment('Hurray! Dry run was successful!');
                return Command::SUCCESS;
            }

            $result = $telegram->setWebhook($bot_config->getWebhookUrl());

            if ($result->isOk()) {
                $this->comment($result->getDescription());
            }
        } catch (TelegramException $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

}
