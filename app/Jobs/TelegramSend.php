<?php

namespace App\Jobs;

use App\Bot\General\TelegramPost;
use App\Models\TelegramRobotMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TelegramSend implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bot;
    protected $parameters;
    protected $mode;
    protected $msg_id;
    protected $forward_id;
    protected $telegram_post;

    public function __construct($bot, $method, $parameters = [], $msg_id = null, $forward_id = null, $mode = 'json')
    {
        if (!is_string($method)) {
            Log::error("Method name must be a string\n");
            return false;
        }

        if (!is_array($parameters)) {
            Log::error("Parameters must be an array\n");
            return false;
        }

        if ($mode == 'json') {
            foreach ($parameters as $key => &$val) {
                // encoding to JSON array parameters, for example reply_markup
                if (!is_numeric($val) && !is_string($val)) {
                    $val = json_encode($val);
                }
            }
        }

        $this->bot = $bot;
        $this->msg_id = $msg_id;
        $this->mode = $mode;
        $this->forward_id = $forward_id;
        $parameters["method"] = $method;
        $this->parameters = $parameters;

    }

//------------------------------------------------------------------------------

    public function handle()
    {
        $this->telegram_post = new TelegramPost($this->bot);

        try {
            if (isset($this->parameters['chat_id'])) {
                $this->sendChatAction($this->parameters['chat_id'], $this->parameters['method']);
            }
            $ret = $this->telegram_post->send($this->mode, $this->parameters['method'], $this->parameters);

            $teleg_msg_id = $ret['message']['result']['message_id'] ?? null;

            if(isset($this->msg_id))
                TelegramRobotMessage::find($this->msg_id)->update([
                    'update_id'    => $teleg_msg_id,
                    'status'       => 'sent',
                    'reply_status' => $ret['status'],
                ]);

            if($teleg_msg_id && !empty($this->forward_id)){
                $this->forwardMessage($teleg_msg_id, $this->forward_id);
            }

        } catch (\Exception $e) {
            getFormedError($e);
        }
    }

//------------------------------------------------------------------------------

//------------------------------------------------------------------------------
    protected function forwardMessage($teleg_message_id, $forward_id){
        if(is_array($forward_id)){
            foreach($forward_id as $item) $this->forwardMessage($teleg_message_id, $item);
            return;
        }

        $tmp = [
            'chat_id' => $forward_id,
            'from_chat_id' => $this->parameters['chat_id'],
            'message_id' => $teleg_message_id,
        ];
        $tmp['disable_notification'] = false;

        $this->telegram_post->send('post',"forwardMessage", $tmp);
    }

//------------------------------------------------------------------------------

    protected function sendChatAction($chat_id, $mode)
    {
        //Actions
        //typing for text messages,
        //upload_photo for photos,
        //record_video or upload_video for videos,
        //record_audio or upload_audio for audio files,
        //upload_document for general files,
        //find_location for location data,
        //record_video_note or upload_video_note for video notes

        $action = [
            'sendInlineMessage'      => 'typing',
            'editMessageReplyMarkup' => 'typing',
            'sendMessage'            => 'typing',
            'forwardMessage'         => 'typing',
            'editMessageText'        => 'typing',
            'sendPhoto'              => 'upload_photo',
            'sendVideo'              => 'upload_video',
        ];

        if (!isset($action[$mode])) return;

        $tmp = [
            'chat_id' => $chat_id,
            'action'  => $action[$mode],
        ];

        return $this->telegram_post->send('post',"sendChatAction", $tmp);
    }

//------------------------------------------------------------------------------

}
