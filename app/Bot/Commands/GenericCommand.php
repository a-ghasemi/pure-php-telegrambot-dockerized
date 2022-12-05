<?php
namespace App\Bot\Commands;

use App\Bot\General\ExtendedSystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class GenericCommand extends ExtendedSystemCommand
{
    protected $name = 'generic';

    protected $description = 'Handles generic commands or is executed by default when a command is not found';

    protected $version = '1.1.0';

    public function execute(): ServerResponse
    {
//        $message = $this->getMessage();
//        $command = $message->getCommand();
//        $callback_query = $this->getCallbackQuery();
//        $callback_data  = $callback_query->getData();

        $command = $this->session->executed_command;

//        $this->debugLog("[generic]");

        return $command ? $this->telegram->executeCommand($command) : Request::emptyResponse();
    }
}
