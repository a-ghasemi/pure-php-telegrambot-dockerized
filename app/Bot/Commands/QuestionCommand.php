<?php

namespace App\Bot\Commands;

use App\Bot\General\ExtendedSystemCommand;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\TelegramId;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class QuestionCommand extends ExtendedSystemCommand
{
    protected $name = 'question';
    protected $description = 'Start getting new question';
    protected $usage = '/question';

    public function execute(): ServerResponse
    {
        if ($this->getMessage()->getCommand() == 'question') {
            $this->session->refresh(true);
            $this->session->state = 'base';
        }

        $state = $this->session->state ?? 'base';

        $this->robotLog($state);

        switch ($state) {
            case 'base':
                return $this->showWelcome();
                break;
            case 'ask_for_question_content':
                return $this->getQuestionContent();
                break;
            case 'ask_for_question_title':
                return $this->getQuestionTitle();
                break;
            case 'ask_for_category':
                return $this->getCategory();
                break;
            case 'has_new_category':
                return $this->getNewCategory();
                break;
            case 'request_user_info':
                    return $this->getUserInfo();
                break;
        }

        return $this->replyToChat(__('bot.command.wrong'));;
    }

    protected function showWelcome(): ServerResponse
    {
        $this->replyToChat(__('bot.question.welcome'), [
                'reply_markup' => Keyboard::remove(['selective' => true])
            ],
        );

        return $this->askForQuestion();
    }

    protected function askForQuestion(): ServerResponse
    {
        $this->session->state = 'ask_for_question_content';
        return $this->replyToChat(__('bot.question.content.ask'), [
            'reply_markup' => Keyboard::remove(['selective' => true]),
        ]);
    }

    protected function getQuestionContent(): ServerResponse
    {
        $question_type = $this->getMessage()->getType();

        if($question_type == 'voice'){
            $download_path = $this->telegram->getDownloadPath();
            $question = $this->getMessage()->getVoice();

            $file_id = $question->getFileId();
            $file    = Request::getFile(['file_id' => $file_id]);
            if ($file->isOk() && Request::downloadFile($file->getResult())) {
                $question = $download_path . '/' . $file->getResult()->getFilePath();
            } else {
                $question = "Failed to download | file_id:{$file_id}";
            }

        }
        else{
            $question = $this->getMessage()->getText(true);
        }

        $question_record = Question::create([
            'question_category_id' => null,
            'telegram_user_id'     => null,
            'title'                => null,
            'type'                 => $question_type,
            'content'              => $question,
            'order'                => 0,
            'status'               => 'pending',
        ]);

        $this->session->question_id = $question_record->id;

        $this->replyToChat(__('bot.question.content.got_it'));

        $this->session->state = 'ask_for_question_title';
        return $this->replyToChat(__('bot.question.title.ask'), [
            'reply_markup' => Keyboard::remove(['selective' => true]),
        ]);
    }

    protected function getQuestionTitle(): ServerResponse
    {
        $question_title = $this->getMessage()->getText(true);

        Question::find($this->session->question_id)->update([
            'title' => $question_title,
        ]);

        $this->replyToChat(__('bot.question.title.got_it'));

//        # make category buttons
//        $categories = QuestionCategory::where('status','published')
//            ->orderBy('order')->orderBy('id')
//            ->get(['id','title'])
//            ->pluck('title','id');
//
//        $keyboard = [];
//        $item = [];
//        foreach($categories as $id => $title){
//            $item[] = ['text' => $title, 'callback_data' => "cat-{$id}"];
//            if(count($item) >= 2){
//                $keyboard[] = $item;
//                $item = [];
//            }
//        }
//        $keyboard[] = [['text' => __('bot.buttons.new_category'),'callback_data' => 'cat-new']];
//
//        $keyboard = (new Keyboard(...$keyboard));
//
//        $this->session->state = 'ask_for_category';
//        return $this->replyToChat(__('bot.question.set_category'), [
//            'reply_markup' => $keyboard,
//        ]);
        if (!$this->user) {
            $this->session->state = 'request_user_info';
            return $this->replyToChat(__('bot.auth.send'), [
                'reply_markup' => (new Keyboard(
                    (new KeyboardButton('ثبت اطلاعات تماس'))
                        ->setRequestContact(true)
                ))
                ->setOneTimeKeyboard(true)
                ->setResizeKeyboard(true)
                ->setSelective(true)
            ]);
        }
        else{
            $this->session->state = 'finish_question';
            return $this->replyToChat(__('bot.question.finished'), [
                'reply_markup' => Keyboard::remove(),
            ]);
        }

    }

    protected function getCategory(): ServerResponse
    {
        $message = $this->getMessage();
        $command = $message->getCommand();
        $this->robotLog($command);

        Question::find($this->session->question_id)->update([
            'category_id' => intval($command),
        ]);

        $this->replyToChat(__('bot.category.got_it'));

        return $this->replyToChat(__('bot.category.registered'), [
            'reply_markup' => Keyboard::remove(['selective' => true]),
        ]);
    }

    protected function getUserInfo(): ServerResponse
    {
        $message = $this->getMessage();

        if($message->getContact() == null){
            return $this->replyToChat(__('bot.content.wrong'));
        }

        $tid = TelegramId::create([
            'user_id' => null,
            'telegram_id' => $message->getFrom()->getId(),
            'phone_number' => $message->getContact()->getPhoneNumber(),
            'username' => $message->getFrom()->getUsername(),
            'firstname' => $message->getFrom()->getFirstName(),
            'lastname' => $message->getFrom()->getLastName(),
            'language' => $message->getFrom()->getLanguageCode(),
        ]);

        Question::find($this->session->question_id)->update([
            'telegram_user_id' => $tid->id,
        ]);


        return $this->replyToChat(__('bot.question.finished'));
    }

}
