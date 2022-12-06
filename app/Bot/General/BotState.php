<?php

namespace App\Bot\General;

use App\Bot\General\BotMachine as BM;

abstract class BotState
{
    public $base_message = null;
    public $messages = [];
    // footer btns : 'btn1'=>null | 'btn2'=>'request_contact' | 'btn3'=>'request_location'
    // inline btns : 'btn1'=>['callback_data'=>'callback_value'] | 'btn2'=>['url'=>'https:// | tg://'] | 'btn3'=>['.....','condition'=>boolean_phrase ]
    // [ btn1 , btn2 ] , [ btn3 ] , [ btn4 , btn5 , btn6 ] , ...
    public $buttons = [];
    public $btn_type = 'footer';// Default Must Be Footer, to remove prev footer buttons  // footer | inline | text(not implemented yet)
    public $msg_type = 'HTML';// HTML | Markdown
    public $update = false;

//    var $allowed_inputs = [];//buttons,text,location,....

//    function __construct() {
//    }

    function before(){
        $this->start();
    }
    function after(){
        if(in_array($this->btn_type , ['footer','footer_sticky']) ){BM::setCache('has_footer',true);}
        $this->end();
    }
    function start(){}
    function end(){}
    function error_state(){
        return false;
    }
    function error(){}
    function action(){}
    function act(){
        if($this->error_state()){ return $this->error(); }
        $this->action();
    }

    function countMessages(){
        return count($this->messages);
    }

    function addMessage($msg){
        $this->messages[] = $msg;
    }

    function getMessages(){
        if(empty($this->base_message) && $this->countMessages()>0){
            $this->base_message = $this->messages[0];
            $this->messages = array_slice($this->messages,1);
        }
        return $this->messages;
//        return array_merge(array_slice($this->messages, 1),array_slice($this->messages, 0, 1));
    }
}
