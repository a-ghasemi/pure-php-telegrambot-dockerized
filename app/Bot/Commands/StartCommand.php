<?php

namespace App\Bot\Commands;

use App\Bot\General\ExtendedSystemCommand;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\TelegramId;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use MongoDB\Driver\Server;

class StartCommand extends ExtendedSystemCommand
{
    protected $name = 'start';
    protected $description = 'Start command';
    protected $usage = '/start';

    public function execute(): ServerResponse
    {
        if ($this->getMessage()->getCommand() == 'start') {
            $this->session->refresh();
            $this->session->state = 'base';
        }

        $state = $this->session->state ?? 'base';

        $this->debugLog("status: " . $state);

        switch ($state) {
            case 'base':
                return $this->showWelcome();
                break;
        }

        return $this->replyToChat(__('bot.command.wrong'));;
    }

    protected function showWelcome(): ServerResponse
    {
        return ($this->user)?$this->showUserMenu():$this->showPublicMenu();
    }

    protected function showUserMenu(): ServerResponse
    {
        $this->session->state = 'user_menu';

        $this->replyToChat(__('bot.menu.user_welcome',['name' => $this->user->firstname ?? $this->user->username]));
        return $this->replyToChat(__('bot.menu.items.ask_question'));
    }

    ## Checked
    protected function showPublicMenu(): ServerResponse
    {
        $this->session->state = 'public_menu';

        $this->replyToChat(__('bot.menu.public_welcome'));
        return $this->replyToChat(__('bot.menu.items.ask_question'));
    }


}
