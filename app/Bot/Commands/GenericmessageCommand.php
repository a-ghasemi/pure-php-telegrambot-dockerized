<?php

namespace App\Bot\Commands;

use App\Bot\General\ExtendedSystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class GenericmessageCommand extends ExtendedSystemCommand
{
    protected $name = 'genericmessage';
    protected $description = 'Handle generic message';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();

        $command = $this->session->executed_command;

//        $this->debugLog("[generic]");

        return $command ? $this->telegram->executeCommand($command) : Request::emptyResponse();
    }
}
