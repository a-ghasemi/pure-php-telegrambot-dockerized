<?php

namespace App\Bot\Commands;

use App\Bot\General\ExtendedSystemCommand;
use Illuminate\Support\Facades\Log;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;

class StartCommand extends ExtendedSystemCommand
{
    protected $name = 'start';
    protected $description = 'Start command';
    protected $usage = '/start';

    public function execute(): ServerResponse
    {
        if($this->getMessage()->getCommand() == 'start'){
            $this->session->state = 'base';
        }

        $state = $this->session->state ?? 'base';

        $this->debugLog("status: " . $state);

        switch ($state) {
            case 'base':
                return $this->showWelcome();
                break;
            case 'ask_for_question':
                return $this->getQuestion();
                break;
        }

        return $this->replyToChat(__('bot.command.wrong'));;
    }

    protected function showUserMenu(): ServerResponse
    {
        $this->session->state = 'user_menu';

        return $this->replyToChat(__('bot.user_menu.welcome'));
    }

    protected function askForQuestion(): ServerResponse
    {
        $this->session->state = 'ask_for_question';

        return $this->replyToChat(__('bot.question.create'), [
            'reply_markup' => Keyboard::remove(['selective' => true]),
        ]);
    }

    protected function getQuestion(): ServerResponse
    {
        $this->session->state = 'get_question';

        $this->getMessage()->getText(true);

        $this->replyToChat(__('bot.question.got_it'));

        return $this->replyToChat(__('bot.question.registered'), [
            'reply_markup' => Keyboard::remove(['selective' => true]),
        ]);
    }

    protected function showWelcome(): ServerResponse
    {
        $this->replyToChat(__('bot.start.welcome'));

        if ($this->user) {
            return $this->showUserMenu();
        }

        return $this->askForQuestion();
    }

}
