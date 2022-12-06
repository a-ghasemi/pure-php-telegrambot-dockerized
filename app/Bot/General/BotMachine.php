<?php

namespace App\Bot\General;

use App\Models\BotUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

abstract class BotMachine
{
    const CACHE_LIFE_TIME = 0;
    const DEBUG_USRS_ID = [
        '91173754',
        '115724262',
    ];

// empty = all
// 201:inputs
// 202:states before
// 203:cache
// 204:states after
// 205:input type && last params
    const DEBUG_CODES = [201, 203];
    const HELLO_TIME = 7200;
    public static $curr_state;
    public static $CLASS_NAME;
    public static $chat_id = null;
    public static $user_id = null;
    public static $input_type = null;
    public static $input_subtype = null;
    public static $input = null;
    public static $input_text = null;
    public static $input_btn = null;
    public static $input_cmd = null;
    public static $outter_cmd = null;
    public static $input_param = null;
    public static $inline_input = null;
    public static $msg_type = null;
    public static $bot = null;
    protected static $states = [];
    protected static $base_state;
    protected static $prev_state;
    protected static $cache;
    protected static $operator = null;
    protected static $input_id = null;
    protected static $response_id = null;
    protected static $last_params = null;
    protected static $request = null;

    public static function clearInputs()
    {
        static::$input_type = null;
        static::$input_subtype = null;
        static::$input_text = null;
        static::$input_btn = null;
        static::$input_cmd = null;
        static::$input_param = null;
        static::$inline_input = null;
        static::$input = null;
    }

    function __construct(Request $request, $bot)
    {//after complete move, change bot to bot_id, TelegramController need change too
        define('DEBUG_MODE', false);
        static::begin();
        if (DEBUG_MODE) {
            mylog('Telegram Request', 'info', $request->all());
        }
        static::$CLASS_NAME = get_called_class();
        static::$bot = $bot;
        static::$request = $request->all();
        static::$operator = new TelegramBotOperator(static::$bot);

        static::$input_type = static::$operator->getReplyType(static::$request);
        static::$input_subtype = static::$operator->getReplySubtype(static::$request, static::$input_type);
        static::$input = static::$request[static::$input_type] ?? null;
        static::$input_text = static::$input['text'] ?? null;
        static::$input_btn = static::$input['data'] ?? null;
        static::$outter_cmd = (strpos(static::$input_btn, 'OC||') === 0) ? substr(static::$input_btn, 4) : null;
        static::$user_id = static::$input['from']['id'] ?? static::$input['message']['from']['id'] ?? null;
        static::$response_id = static::$input['id'] ?? null;
        static::$input_id = static::$input['message']['message_id'] ?? static::$response_id;
        static::$chat_id = static::$input['chat']['id'] ?? static::$input['message']['chat']['id'] ?? null;
        if (isset(static::$input_text)) {
            static::$inline_input = substr(static::$input_text, 0, strpos(static::$input_text, '@') ?? strlen(static::$input_text));
            static::$input_param = (strpos(static::$input_text, '/') === 0 && strpos(static::$input_text, ' ') > 0) ? trim(substr(static::$input_text, strpos(static::$input_text, ' '))) : null;
            static::$input_cmd = (strpos(static::$input_text, '/') === 0) ? trim(substr(static::$input_text, 0, strpos(static::$input_text, ' ') > 0 ? strpos(static::$input_text, ' ') : strlen(static::$input_text))) : null;
        }

        static::$msg_type = isset(static::$input['entities'][0]['type']) ? static::$input['entities'][0]['type']
            : (isset(static::$input['photo']) ? 'photo'
                : (isset(static::$input['location']) ? 'location'
                    : (isset(static::$input['contact']) ? 'contact'
                        : (isset(static::$input['data']) ? 'data'
                            : (isset(static::$input['text']) ? 'text' : 'unknown')
                        ))));

//        TelegramRobotsUser::add(null, static::$user_id, static::$bot->id, static::$input_cmd);

        if (DEBUG_MODE && (empty(static::DEBUG_CODES) || in_array(201, static::DEBUG_CODES))) {
            mylog('Bot Machine | 201', 'info', [
                'input_type'    => static::$input_type,
                'input_subtype' => static::$input_subtype,
                'input_text'    => static::$input_text,
                'input_btn'     => static::$input_btn,
                'outter_cmd'    => static::$outter_cmd,
                'input_cmd'     => static::$input_cmd,
                'input_param'   => static::$input_param,
                'inline_input'  => static::$inline_input,
                'input_id'      => static::$input_id,
                'resp_id'       => static::$response_id,
                'input'         => static::$input,
            ]);
        }

        self::$cache = Cache::get('newbot_data_' . static::$bot->id . '_' . static::$chat_id);

        $rb_state = static::base_state();
        if (DEBUG_MODE && (empty(static::DEBUG_CODES) || in_array(202, static::DEBUG_CODES))) {
            mylog('Bot Machine | 202', 'info', ['reboot_state' => $rb_state, 'base_state' => static::$base_state,
                                                'curr_state'   => static::$curr_state]);
        }
        if (is_null($rb_state)) {
            Cache::forget('newbot_data_' . static::$bot->id . '_' . static::$chat_id);
            self::$cache = null;
        } elseif ($rb_state !== false) {
            static::$base_state = $rb_state;
        } else {
            self::$cache = Cache::get('newbot_data_' . static::$bot->id . '_' . static::$chat_id);
        }
        if (DEBUG_MODE && (empty(static::DEBUG_CODES) || in_array(203, static::DEBUG_CODES))) {
            mylog('Bot Machine | 203', 'info', [
                'cache' => static::$cache,
            ]);
        }

        static::config();
        try {
            self::$curr_state = self::getCache('curr_state', static::$base_state);
            if (DEBUG_MODE && (empty(static::DEBUG_CODES) || in_array(204, static::DEBUG_CODES))) {
                mylog('Bot Machine | 204', 'info', ['base_state' => static::$base_state,
                                                    'curr_state' => self::$curr_state]);
            }
            self::$prev_state = self::getCache('prev_state', null);
            self::setCache('last_seen', time());

            static::run_state();

            self::setCache('curr_state', self::$curr_state);
            self::setCache('prev_state', self::$prev_state);

            if (DEBUG_MODE && (empty(static::DEBUG_CODES) || in_array(205, static::DEBUG_CODES)) && in_array(static::$user_id, static::DEBUG_USRS_ID)) {
                mylog('Bot Machine | 205', 'info', ['User ID' => static::$user_id
                    , 'Input Type'                            => static::$input_type
                    , 'Last Params'                           => static::$last_params,
                ]);
                static::operate(
                    'Debug Message',
                    'sendMessage', [
                    "---[DEBUGER]---\n\n"
                    . "<b>MESSAGE TYPE</b>\n"
                    . "<pre>" . var_export(static::$msg_type, true) . "</pre>\n\n"
                    . "<b>STATE</b>\n"
                    . "<pre>" . static::$curr_state . "</pre>"
                    . "\n\n-------------------",
                ]);
            }
            if (static::CACHE_LIFE_TIME == 0)//forever
                Cache::forever('newbot_data_' . static::$bot->id . '_' . static::$chat_id, self::$cache, static::CACHE_LIFE_TIME);
            else
                Cache::put('newbot_data_' . static::$bot->id . '_' . static::$chat_id, self::$cache, static::CACHE_LIFE_TIME);

        } catch (\Exception $e) {
            getFormedError($e);
        }
    }

    protected abstract static function begin();

    protected static function base_state()
    {
        if (static::$input_text === '/start') {
            return null;
        }//means reset everything, start from base
        $ret = static::reboot_state();
        if ($ret) return $ret;
        if (!Cache::has('newbot_data_' . static::$bot->id . '_' . static::$chat_id)) {
//            static::clearInputs();
            return null;
        }
        return $ret;
    }

    protected static function reboot_state()
    {
        // NULL             reset everything and start from base
        // 'STATE_NAME'     reset everything, start from STATE_NAME state
        // FALSE            dont reset
        return false;
    }

    protected abstract static function config();

    public static function hasCache($var_name, $index = 'base')
    {
        return (isset(self::$cache[$var_name][$index]));
    }

    public static function getCache($var_name, $default_value = null, $index = 'base')
    {
        return (isset(self::$cache[$var_name][$index]) ? self::$cache[$var_name][$index] : $default_value);
    }

    public static function setCache($var_name, $value = null, $index = 'base')
    {
        if (DEBUG_MODE && (empty(static::DEBUG_CODES) || in_array(206, static::DEBUG_CODES))) {
            mylog('Bot Machine | 206', 'info', [
                    'Condition' => !isset(self::$cache[$var_name]),
                    'Set Cache' => ['name' => $var_name, 'value' => $value, 'index' => $index]]
            );
        }
        if (!isset(self::$cache[$var_name])) {
            self::resetCache($var_name, $value, $index);
        } else {
            self::updateCache($var_name, $value, $index);
        }
    }

    protected function run_state()
    {
        static::$last_params['CurrState'][] = self::$curr_state;
        static::$last_params['PrevState'][] = self::$prev_state;

        $state = static::$states[self::$curr_state];
        $state->act();////////////////////////////////////////////////////////// ACT | ACTION
//        if(isset($state->act)){$state->act();}
        static::$last_params['CurrState'][] = self::$curr_state;
        static::$last_params['PrevState'][] = self::$prev_state;

        $state2 = static::$states[self::$curr_state];

        $state2->before();////////////////////////////////////////////////////// BEFORE | START

        if (DEBUG_MODE && (empty(static::DEBUG_CODES) || in_array(208, static::DEBUG_CODES))) {
            mylog('Bot Machine | 208', 'info', [
                    'Condition' => [static::getCache('has_footer', false)
                        , !static::getCache('dont_clear_btn', true)
                        , !in_array(static::$states[static::$curr_state]->btn_type, ['footer', 'footer_sticky'])],
                    'Clear Footer Buttons']
            );
        }
        if (
            static::getCache('has_footer', false)
            && !static::getCache('dont_clear_btn', true)
            && !in_array(static::$states[static::$curr_state]->btn_type, ['footer', 'footer_sticky'])
        ) {
            static::operate(
                'footer cleaner',
                'clearFooterButtons');
            static::removeCache('has_footer');
        }

        foreach ($state2->getMessages() as $i => $message) {
            static::operate(
                'StateMsg::' . self::$curr_state,
                'mainSendMessage', [$message, [], 'footer',
                $state2->msg_type]);//after all, make this an auto-make type order about content of message (text, image, ...)
        }

        if (isset($state2->base_message)) {/////////////////////////////////////// MESSAGES
            static::operate(
                'StateBaseMsg::' . self::$curr_state,
                'mainSendMessage', [$state2->base_message, $state2->buttons, $state2->btn_type, $state2->msg_type,
                ($state2->update) ? static::$input_id : false]);
        }
        $state2->after();/////////////////////////////////////////////////////// AFTER | END

        static::$last_params['CurrState'][] = self::$curr_state;
        static::$last_params['PrevState'][] = self::$prev_state;
    }

    public static function operate($title, $botcommand, $inp = null, $bot_id = null, $user_id = null, $chat_id = null)
    {
        if (is_null($bot_id)) {
            $bot_id = static::$bot->id;
        }
        if (is_null($chat_id)) {
            $chat_id = (is_null($user_id)) ? static::$chat_id : User::find($user_id)->telegram_uid;
        }
        $operator = new TelegramBotOperator($bot_id, $title);
        return call_user_func_array([$operator,
            $botcommand], !is_null($inp) ? array_merge([$chat_id], $inp) : [$chat_id]);
    }

    public static function removeCache($var_name, $index = null)
    {
        if (!is_null($index)) {
            unset(self::$cache[$var_name][$index]);
        } else {
            unset(self::$cache[$var_name]);
        }
    }

    public static function setState($state_name, $push_message = null)
    {
        if (!self::stateExists($state_name)) {
            mylog('Bot Machine | State Doesn\'t Exists', 'error', 'Calling Not-Existed State -> ' . $state_name);
            return false;
        }

        self::$prev_state = self::$curr_state;
        self::$curr_state = $state_name;
        if (!is_null($push_message)) {
            $state = static::$states[self::$curr_state];
            $state->addMessage($push_message);
        }
        return true;
    }

    public static function stateExists($state_name)
    {
        return isset(static::$states[$state_name]);
    }

    public static function backState($push_message = null)
    {
        if (is_null(self::$prev_state)) {
            return false;
        }
        self::$curr_state = self::$prev_state;
        self::$prev_state = null;
        if (!is_null($push_message)) {
            $state = static::$states[self::$curr_state];
            $state->addMessage($push_message);
        }
        return true;
    }

    public static function setIfNotCache($var_name, $value = null, $index = 'base')
    {
        if (DEBUG_MODE && (empty(static::DEBUG_CODES) || in_array(207, static::DEBUG_CODES))) {
            mylog('Bot Machine | 207', 'info', [
                    'Condition'        => [!isset(self::$cache[$var_name]), !isset(self::$cache[$var_name][$index])],
                    'Set If Not Cache' => ['name' => $var_name, 'value' => $value, 'index' => $index]]
            );
        }
        if (
            !isset(self::$cache[$var_name])
            || !isset(self::$cache[$var_name][$index])
        ) {
            self::resetCache($var_name, $value, $index);
        }
    }

    public static function resetCache($var_name, $value = null, $index = 'base')
    {
        self::$cache[$var_name] = [$index => $value];
    }

    public static function incCache($var_name, $index = 'base', $min = 1, $count = 1)
    {
        if (DEBUG_MODE && (empty(static::DEBUG_CODES) || in_array(211, static::DEBUG_CODES))) {
            mylog('Bot Machine | 211', 'info', [
                    'Inc Cache' => ['name' => $var_name, 'index' => $index]]
            );
        }
        static::setCache($var_name, floatval(static::getCache($var_name, $min, $index)) + $count, $index);
    }

    public static function decCache($var_name, $index = 'base', $min = 1, $count = 1)
    {
        if (DEBUG_MODE && (empty(static::DEBUG_CODES) || in_array(212, static::DEBUG_CODES))) {
            mylog('Bot Machine | 212', 'info', [
                    'Dec Cache' => ['name' => $var_name, 'index' => $index]]
            );
        }
        static::setCache($var_name, max($min, floatval(static::getCache($var_name, $min, $index)) - $count), $index);
    }

    public static function pushCache($var_name, $value = null, $index = 'base')
    {
        if (DEBUG_MODE && (empty(static::DEBUG_CODES) || in_array(215, static::DEBUG_CODES))) {
            mylog('Bot Machine | 215', 'info', [
                    'Condition'  => !isset(self::$cache[$var_name]),
                    'Push Cache' => ['name' => $var_name, 'value' => $value, 'index' => $index]]
            );
        }
        if (!isset(self::$cache[$var_name])) {
            self::resetCache($var_name, [$value], $index);
        } else {
            $tmp = self::$cache[$var_name];
            array_push($tmp[$index], $value);
            self::$cache[$var_name] = $tmp;
        }
    }

    public static function popCache($var_name, $default_value = null, $index = 'base')
    {
        if (DEBUG_MODE && (empty(static::DEBUG_CODES) || in_array(216, static::DEBUG_CODES))) {
            mylog('Bot Machine | 216', 'info', [
                    'Condition' => !isset(self::$cache[$var_name]),
                    'Pop Cache' => ['name' => $var_name, 'default_value' => $default_value, 'index' => $index]]
            );
        }
        if (isset(self::$cache[$var_name])) {
            $tmp = self::$cache[$var_name];
            $val = array_pop($tmp[$index]);
            return $val;
        }

        return null;
    }

    public static function clearCache()
    {
        Cache::forget('newbot_data_' . static::$bot->id . '_' . static::$chat_id);
    }

    public static function flushCache($var_name, $default_value = null, $index = 'base')
    {
        $tmp = self::getCache($var_name, $default_value, $index);
        self::removeCache($var_name, $index);
        return $tmp;
    }

    public static function flushFullCache($var_name, $default_value = [])
    {
        $tmp = self::getFullCache($var_name, $default_value);
        self::removeCache($var_name);
        return $tmp;
    }

    public static function getFullCache($var_name, $default_value = [])
    {
        return (isset(self::$cache[$var_name]) ? self::$cache[$var_name] : $default_value);
    }

    private static function updateCache($var_name, $value = null, $index = 'base')
    {
        $tmp = self::$cache[$var_name];
        $tmp[$index] = $value;
        self::$cache[$var_name] = $tmp;
    }

    public static function getBotUser()
    {
        $botUser = self::getCache('bot_user');

        if (!$botUser) {
            $telegram_uid = self::$input['from']['id'] ?? null;
            $botUser = BotUser::where('user_telegram_uid', $telegram_uid)->first();

            if (!$botUser && $telegram_uid) {
                $botUser = BotUser::create([
                    'robot_id'           => static::$bot->id,
                    'user_telegram_uid'  => $telegram_uid,
                    'telegram_username'  => self::$input['from']['username'] ?? null,
                    'telegram_firstname' => self::$input['from']['first_name'] ?? null,
                    'telegram_lastname'  => self::$input['from']['last_name'] ?? null,
                    'telegram_lang_code' => self::$input['from']['language_code'],
                ]);

                mylog('Robot Membership', 'ding',
                    'Telegram ID: ' . (self::$input['from']['id'] ?? '---') . "\n" .
                    'First Name: ' . (self::$input['from']['first_name'] ?? '---') . "\n" .
                    'Last Name: ' . (self::$input['from']['last_name'] ?? '---') . "\n" .
                    'Username: ' . (self::$input['from']['username'] ?? '---') . "\n" .
                    'Language Code: ' . (self::$input['from']['language_code'] ?? '---') . "\n",
                    false
                );
                self::setCache('bot_user', $botUser);
            }
        }
        return $botUser;
    }


//    protected static function commandCheck($inp){
//        return (static::$input_cmd == $inp);
//    }


    public static function runShellCommand($title, $command): string
    {
        $ret = runShell($command, true);
        $message = $ret['output'] . "\n------------\n" . $ret['errors'];
        $return = $title?"{$title} Result:\n":'';
        $return .= "<pre>" . htmlentities($message) . "</pre>";
        return $return;
    }

}
