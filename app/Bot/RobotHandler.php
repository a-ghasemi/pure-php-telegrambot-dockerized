<?php
namespace App\Bot;

use Packages\BotConfig;
use App\Bot\General\BotMachine as BM;
use App\Bot\General\BotState;

class RobotHandler extends BM
{
    const DEBUG_MODE = false;

    static $base_state = 'welcome';
    static $temp = null;

//==================================================================================================
    protected static function begin(){
        define('LANGUAGE' , 'en');
    }

    protected static function reboot_state(){
        if(BM::$input_btn === 'gobase') {return null;}
        return false;
    }

//==================================================================================================
    protected static function config() {

        static::$states['welcome'] = new class extends BotState
        {
            function start(){
                $this->base_message = "<b>Good Day!</b>";
                $this->base_message .= '\nI\'m under constructions';
                $this->base_message .= "\nand I'll ready soon";
            }
        };
//==================================================================================================
//==================================================================================================
//==================================================================================================
// [Sample State] ==================================================================================
        /*
                static::$states['new_state'] = new class extends BotState{

                    var $btn_type = 'inline';

                    function start(){
                        $this->base_message = "Dynamic Message";
                        $this->buttons[]['button_title'] = ['callback_data'=>'button_data','condition'=>true];

                        //do somthing on start (before first message, at object creation)
                    }

                    function action(){
                        return BM::backState();

                        switch(BM::$input_text){
                            case '/new':
                                return BM::setState('other_state');
                            break;
                            case '/back':
                                return BM::backState();
                            break;
                            case '/addMessage':
                                $this->addMessage("Hi!");
                            break;
                            default:
                                $this->addMessage("Unknown Command\n".BM::$input_text);
                        }
                    }

                };
        */
//==================================================================================================
    }
}
