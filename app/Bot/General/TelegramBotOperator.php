<?php
# Mother is: app/Http/Controllers/System/TelegramBots/_dev/BaseTelegramOperator.php
namespace App\Bot\General;

use App\Jobs\TelegramSend;
use App\Bot\General\TelegramPost;
use App\Models\BotConnection;

# Uses for sending commands to telegram server
class TelegramBotOperator
{
    const CACHE_LIFE_TIME = 30;
    const DEBUG_MODE = false;
    const DEBUG_USRS_ID = ['91173754', '115724262'];

    protected $bot = null;
    protected $title = null;
    protected $operable = false;

    function __construct($bot, $title = null)
    {
        {
            #findOrFail Causes too many headaches :/ Don't do this
            $this->bot = is_numeric($bot)? BotConnection::where('active',true)->find($bot): $bot;
            $this->title = $title;

            $this->operable = !is_null($this->bot);
        }
    }

//------------------------------------------------------------------------------
    # commands : ['com1'=>null, 'com2'=>'request_contact', 'com3'=>'request_location']
    # parse_mode : HTML || Markdown || null
    public function sendMessageInlineMarkup($chat_id, $content, $commands = null, $html_mark = 'HTML', $cols = 2, $delete_last = false, $web_preview = false)
    {
        if(!$this->operable) return;

//        if($delete_last) $this->deleteLastMessage($chat_id,$reply_id);

        $tmp = [
            'chat_id' => $chat_id,
            "text"    => $content,
        ];

        if ($html_mark) $tmp['parse_mode'] = $html_mark;
        $tmp['disable_web_page_preview'] = !$web_preview;
        if ($commands) {
            $tmp['reply_markup']['inline_keyboard'] = [];

            $c = 1;
            $kb = [];
            $kbs = [];
            foreach ($commands as $command => $params) {
                if (isset($params['condition'])) {
                    if ($params['condition'] === false) continue;
                    unset($params['condition']);
                }

                $t = ['text' => $command];
                $t = array_merge($t, $params);
                $kb[] = $t;

                if ($c++ % $cols == 0) {
                    $kbs[] = $kb;
                    $kb = [];
                }
            }

            if (!empty($kb)) {
                $kbs[] = $kb;
            }

            if (!empty($kbs)) {
                $tmp['reply_markup']['inline_keyboard'] = $kbs;
//                $tmp['reply_markup']['one_time_keyboard'] = true;
//                $tmp['reply_markup']['resize_keyboard'] = true;
            }
        }
        elseif (is_array($commands)) {
            $tmp['reply_markup']['remove_keyboard'] = true;
//            $tmp['reply_markup'] = 'replyKeyboardHide';
        }


        $this->TelegramSendJob("sendMessage", $tmp);
    }

    //------------------------------------------------------------------------------
//------------------------------------------------------------------------------
    public function deleteLastMessage($chat_id,$reply_id){
        if(!$this->operable) return;

        $tmp = [
            'chat_id' => $chat_id,
            "message_id" => $reply_id,
        ];

        $this->TelegramSendJob( "deleteMessage", $tmp );
    }

//------------------------------------------------------------------------------
    public function sendMessage($chat_id,$content,$commands=null,$html_mark='HTML',$cols=2,$deleteLast = false){//commands : ['com1'=>null,'com2'=>'request_contact','com3'=>'request_location']
        if(!$this->operable) return;

//        if($deleteLast) $this->deleteLastMessage($chat_id,$reply_id);
        $tmp = [
            'chat_id' => $chat_id,
            "text" => $content,
        ];

        if($html_mark) $tmp['parse_mode'] = $html_mark;
        $tmp['disable_web_page_preview'] = true;
        if($commands) {
            $tmp['reply_markup']['keyboard'] = [];

            $c = 1;
            $kb = [];
            $kbs = [];
            foreach($commands as $command=>$params)
            {
                if(isset($params['condition'])){
                    if($params['condition'] === false) continue;
                    unset($params['condition']);
                }

                $t = ['text' => $command];
                if(isset($params['type'])) $t[$params['type']] = true;
                $kb[] = (isset($params['type']))?$t:$t['text'];

                if($c++%$cols == 0){$kbs[] = $kb;$kb=[];}
            }

            if(!empty($kb)){
                $kbs[] = $kb;
            }

            if(!empty($kbs)){
                $tmp['reply_markup']['keyboard'] = $kbs;
                $tmp['reply_markup']['one_time_keyboard'] = true;
                $tmp['reply_markup']['resize_keyboard'] = true;
            }
        }
        elseif(is_array($commands)){
            $tmp['reply_markup']['remove_keyboard'] = true;
//            $tmp['reply_markup'] = 'replyKeyboardHide';
        }



        $this->TelegramSendJob( "sendMessage", $tmp );
    }

//------------------------------------------------------------------------------
    // $commands_type = footer | inline | text(not implemented yet)
    // footer btns : 'btn1'=>null | 'btn2'=>'request_contact' | 'btn3'=>'request_location'
    // inline btns : 'btn1'=>['callback_data'=>'callback_value'] | 'btn2'=>['url'=>'https:// | tg://'] | 'btn3'=>['.....','condition'=>boolean_phrase ]
    // [ btn1 , btn2 ] , [ btn3 ] , [ btn4 , btn5 , btn6 ] , ...
    public function clearFooterButtons($chat_id){
        if(!$this->operable) return;
        $this->mainSendMessage($chat_id,"_",[],'footer');
    }

    public function mainSendMessage($chat_id,$content,$commands=null,$commands_type=null,$html_mark='HTML',$is_update = false){
        if(!$this->operable) return;

        $tmp = [
            'chat_id' => $chat_id,
            'text' => ($content ?? "_"),
        ];
//        if($is_update === true) {
//            $tmp['message_id'] = $this->last_message_id;
//        }
//        else
        if($is_update !== false) {
            $tmp['message_id'] = $is_update;
        }

//        if($content) $tmp["text"] = $content;

        if($html_mark) $tmp['parse_mode'] = $html_mark;//HTML || Markdown || null
        $tmp['disable_web_page_preview'] = true;

        switch($commands_type){
            case 'footer_sticky':
            case 'footer':
                if(!empty($commands)) {
                    $tmp['reply_markup']['keyboard'] = [];

                    $c = 1;
                    $kbs = [];
                    foreach($commands as $row){
                        $kb = [];
                        foreach($row as $command=>$params)
                        {
                            if(isset($params['condition'])){
                                if($params['condition'] === false) continue;
                                unset($params['condition']);
                            }

                            $t = ['text' => $command];
                            if(isset($params['type'])) $t[$params['type']] = true;
                            $kb[] = (isset($params['type']))?$t:$t['text'];

                        }
                        if(!empty($kb))$kbs[] = $kb;
                    }

                    if(!empty($kbs)){
                        $tmp['reply_markup']['keyboard'] = $kbs;
                        $tmp['reply_markup']['one_time_keyboard'] = ($commands_type !== 'footer_sticky');
                        $tmp['reply_markup']['resize_keyboard'] = true;
                    }
                }
                elseif(is_array($commands)){
                    $tmp['reply_markup']['remove_keyboard'] = true;
//                    $tmp['reply_markup'] = 'replyKeyboardHide';
                }
                break;

            case 'inline':
                if(!empty($commands)) {
                    $tmp['reply_markup']['inline_keyboard'] = [];

                    $c = 1;
                    $kbs = [];
                    foreach($commands as $row){
                        $kb = [];
                        foreach($row as $command=>$params)
                        {
                            if(isset($params['condition'])){
                                if($params['condition'] === false) continue;
                                unset($params['condition']);
                            }

                            $t = ['text' => $command];
                            $t = array_merge($t,$params);
                            $kb[] = $t;

                        }
                        if(!empty($kb))$kbs[] = $kb;
                    }

                    if(!empty($kbs)){
                        $tmp['reply_markup']['inline_keyboard'] = $kbs;
                        //                $tmp['reply_markup']['one_time_keyboard'] = true;
                        //                $tmp['reply_markup']['resize_keyboard'] = true;
                    }
                }
                break;
        }

        $this->TelegramSendJob( ($is_update)?"editMessageText":"sendMessage", $tmp);
//        return $this->apiRequestJson(($is_update)?"editMessageText":"sendMessage", $tmp );

//        $this->last_message_id = 1;
    }

//------------------------------------------------------------------------------
    public function answerCallbackQuery($response_id,$content,$commands=null,$html_mark='HTML',$cols=2,$deleteLast = false){//commands : ['com1'=>null,'com2'=>'request_contact','com3'=>'request_location']
        if(!$this->operable) return;
//        if($deleteLast) $this->deleteLastMessage($chat_id,$reply_id);
        $tmp = [
            'callback_query_id' => $response_id,
            "text" => $content,
        ];

//        if($html) $tmp['parse_mode'] = 'html';
//        $tmp['disable_web_page_preview'] = true;
        if($commands) {
            $tmp['reply_markup']['inline_keyboard'] = [];

            $c = 1;
            $kb = [];
            $kbs = [];
            foreach($commands as $command=>$params)
            {
                if(isset($params['condition'])){
                    if($params['condition'] === false) continue;
                    unset($params['condition']);
                }

                $t = ['text' => $command];
                $t = array_merge($t,$params);
                $kb[] = $t;

                if($c++%$cols == 0){$kbs[] = $kb;$kb=[];}
            }

            if(!empty($kb)){
                $kbs[] = $kb;
            }

            if(!empty($kbs)){
                $tmp['reply_markup']['inline_keyboard'] = $kbs;
//                $tmp['reply_markup']['one_time_keyboard'] = true;
//                $tmp['reply_markup']['resize_keyboard'] = true;
            }
        }
//        elseif(is_array($commands)){
////            $tmp['reply_markup']['remove_keyboard'] = true;
////            $tmp['reply_markup'] = 'replyKeyboardHide';
//        }



        $this->TelegramSendJob( "sendMessage", $tmp);
    }

//------------------------------------------------------------------------------
    public function editMessageReplyMarkup($response_id, $commands=null,$cols=2,$deleteLast = false){//commands : ['com1'=>null,'com2'=>'request_contact','com3'=>'request_location']
        if(!$this->operable) return;
//        if($deleteLast) $this->deleteLastMessage();
        $tmp = [
            'message_id' => $response_id,
        ];

        if($commands) {
            $tmp['reply_markup']['inline_keyboard'] = [];

            $c = 1;
            $kb = [];
            $kbs = [];
            foreach($commands as $command=>$params)
            {
                if(isset($params['condition'])){
                    if($params['condition'] === false) continue;
                    unset($params['condition']);
                }

                $t = ['text' => $command];
                $t = array_merge($t,$params);
                $kb[] = $t;

                if($c++%$cols == 0){$kbs[] = $kb;$kb=[];}
            }

            if(!empty($kb)){
                $kbs[] = $kb;
            }

            if(!empty($kbs)){
                $tmp['reply_markup']['inline_keyboard'] = $kbs;
//                $tmp['reply_markup']['one_time_keyboard'] = true;
//                $tmp['reply_markup']['resize_keyboard'] = true;
            }
        }
        elseif(is_array($commands)){
//            $tmp['reply_markup']['remove_keyboard'] = true;
//            $tmp['reply_markup'] = 'replyKeyboardHide';
        }



        $this->TelegramSendJob( "editMessageReplyMarkup", $tmp );
    }


    /*
     * mode: MonYear, Day, HourMin
     * base: Current Input, Y|m|d|H|i
     * fromPoint: Start From
     * endPoint: Upper Bound Limit, null to Infinite Upper Bound
     * page: send current page
     * itemRowsPerPage: each page has # rows of items (not nav) {Today & Tommorow needs One Row Eachself, Other has two col in one row}
     *
     * fromPoint <= base <= endPoint
     */
    public function dateTimeStream($chat_id,$mode,$base=null,$fromPoint=null,$endPoint=null,$page=1,$itemRowsPerPage=3){
        if(!$this->operable) return;

        $ret = ['items'=> null, 'navs'=> null, 'page'=>0];
        $today = \App\Libraries\jDateTime::date('Y|m|d',time(),false);
        $main_point = \App\Libraries\jDateTime::date('Y|m|d|w|H|i',strtotime("+2 hours", time()),false);
        if(!isset($fromPoint) || compareDates($fromPoint, $main_point, 'Y|m|d|w|H|i')==1 ) $fromPoint = $main_point;
        if(!isset($base) || compareDates($base , $fromPoint, 'Y|m|d|w|H|i')==1 ) $base = $fromPoint;
        $fromPoint = explode('|',$fromPoint);
        $fromPoint = array_map('intval',$fromPoint);
        $base = explode('|',$base);
        $base = array_map('intval',$base);
        $today = explode('|',$today);
        $today = array_map('intval',$today);
        if(!isset($base[5]))$base[5] = 0;
        $max = [0,31,31,31,31,31,31,30,30,30,30,30,29];

        if($base[3] == 9){
            $greg = \App\Libraries\jDateTime::jalaliToGreg_arr($base[0].'/'.$base[1].'/'.$base[2]);
            $greg = mktime(1,0,0,$greg[1],$greg[2],$greg[0]);
            $base[3] = (date('w', $greg)+8)%7;
        }

        if($base[4]==23 && $base[5]==30){
            $base[5]=0;
            $base[4]=0;
            $base[2]++;
            $base[3] = ($base[3]+1)%7;
            if($base[2] > $max[$base[1]]){
                $base[1]++;
                $base[2]=1;
                if($base[1] > 12){
                    $base[0]++;
                    $base[1]=1;
                }
            }
        }

//mylog('Operator Datetime Stream','warning',[$mode,$today,$base,$fromPoint,$endPoint,$page,$itemRowsPerPage]);


        $arr = [];
        $YMDHIS=null;
//        $colsPerRow=2;
        $base[5]=59;
        switch($mode){
            case 'MonYear':// اردیبهشت ۱۳۹۷
                $colsPerRow = 2;
                $YMDHIS = 'M';

                for($i=0; $i < $page*$itemRowsPerPage*$colsPerRow; $i++){
                    $arr['Y'][] = $base[0];
                    $arr['M'][] = $base[1];
                    $base[1]++;
                    if($base[1]>12){$base[1]=1;$base[0]++;}
                }
                break;
            //-----------------------
            case 'Day':// سه شنبه، ۱۴ اردیبهشت-  امروز-  فردا، ۱۵ اردیبهشت
                $colsPerRow = 2;
                $YMDHIS = 'D';

                for($i=$base[2]; $i <= $max[$base[1]]; $i++){
                    $arr['Y'][] = $base[0];
                    $arr['M'][] = $base[1];
                    $arr['D'][] = $i;
                    $arr['W'][] = (7+$base[3]-$base[2]+$i)%7;
                }
                break;
            //-----------------------
            case 'HourMin': // ۱۲:۴۵ ظهر
                $colsPerRow = 2;
                $divEachHour = 2;
                $YMDHIS = 'I';

                $quanta = 60 / $divEachHour;
                for($j=ceil($base[5]/$quanta)*$quanta; $j < 60; $j+=$quanta){
                    $arr['Y'][] = $base[0];
                    $arr['M'][] = $base[1];
                    $arr['D'][] = $base[2];
//                      $arr['W'][] = (7+$base[3]-$base[2])%7;
                    $arr['W'][] = $base[3];
                    $arr['H'][] = $base[4];
                    $arr['I'][] = substr('0'.$j,-2);
                }
                for($i=$base[4]+1; $i <= 23; $i++){
                    for($j=0; $j < 60; $j+=$quanta){
                        $arr['Y'][] = $base[0];
                        $arr['M'][] = $base[1];
                        $arr['D'][] = $base[2];
//                      $arr['W'][] = (7+$base[3]-$base[2])%7;
                        $arr['W'][] = $base[3];
                        $arr['H'][] = $i;
                        $arr['I'][] = substr('0'.$j,-2);
                    }
                }
                break;
        }


        if($page == 'last') {
            $page = ceil(count($arr[$YMDHIS])/$itemRowsPerPage/$colsPerRow);
        }
        else {
            $page = min($page,ceil(count($arr[$YMDHIS])/$itemRowsPerPage/$colsPerRow));
        }

        switch($mode){
            case 'MonYear':// اردیبهشت ۱۳۹۷
                for(
                    $i=($page-1)*$itemRowsPerPage*$colsPerRow;
                    $i < min(count($arr[$YMDHIS]),$page*$itemRowsPerPage*$colsPerRow);
                    $i += $colsPerRow){

                    $tmp = [];
                    for($j=$colsPerRow-1; $j >= 0; $j--){
                        if(isset($arr[$YMDHIS][$i+$j])){
                            $tmp[__('dates.months.'.$arr['M'][$i+$j]).' '.$arr['Y'][$i+$j]] = [
                                'callback_data'=>$mode.'|Y'.$arr['Y'][$i+$j].'|M'.$arr['M'][$i+$j]
                            ];
                        }
                    }
                    if(!empty($tmp)) $ret['items'][] =  $tmp;
                }
                break;
            //-----------------------
            case 'Day':// سه شنبه، ۱۴ اردیبهشت-  امروز-  فردا، ۱۵ اردیبهشت
                for(
                    $i=($page-1)*$itemRowsPerPage*$colsPerRow;
                    $i < min(count($arr[$YMDHIS]),$page*$itemRowsPerPage*$colsPerRow);
                    $i += $colsPerRow){
                    $tmp = [];
                    $tmp2 = [];
                    for($j=$colsPerRow-1; $j >= 0; $j--){
                        if(isset($arr[$YMDHIS][$i+$j])){
                            $is_today = ($today[0] == $arr['Y'][$i+$j]) &&
                                ($today[1] == $arr['M'][$i+$j]) &&
                                ($today[2] == $arr['D'][$i+$j]);

                            $is_tomorrow = ($today[0] == $arr['Y'][$i+$j]) &&
                                ($today[1] == $arr['M'][$i+$j]) &&
                                ($today[2]+1 == $arr['D'][$i+$j]);


                            $tmp[($is_today?__('dates.days.today').', ':(
                            $is_tomorrow?__('dates.days.tomorrow').', ':''))
                            .__('dates.days.'.$arr['W'][$i+$j])
                            .', '.$arr['D'][$i+$j].' '.__('dates.months.'.$arr['M'][$i+$j])
                            ] = [
                                'callback_data'=>$mode.'|Y'.$arr['Y'][$i+$j].'|M'.$arr['M'][$i+$j].'|D'.$arr['D'][$i+$j].'|W'.$arr['W'][$i+$j]
                            ];
                            if($is_today){
                                $tmp2['today'] =  $tmp;
                                $tmp = [];
                            }
                            if($is_tomorrow){
                                $tmp2['tomorrow'] =  $tmp;
                                $tmp = [];
                            }
                        }
                    }
                    if(isset($tmp2['today'])) {
                        $ret['items'][] =  $tmp2['today'];
                    }
                    if(isset($tmp2['tomorrow'])) {
                        $ret['items'][] =  $tmp2['tomorrow'];
                    }
                    if(!empty($tmp)) $ret['items'][] =  $tmp;
                }
                break;
            //-----------------------
            case 'HourMin': // ۱۲:۴۵ ظهر
                for(
                    $i=($page-1)*$itemRowsPerPage*$colsPerRow;
                    $i < min(count($arr[$YMDHIS]),$page*$itemRowsPerPage*$colsPerRow);
                    $i += $colsPerRow){

                    $tmp = [];
                    for($j=$colsPerRow-1; $j >= 0; $j--){
                        if(isset($arr[$YMDHIS][$i+$j])){
                            $tmp[
//                                    __('dates.hours.title').' '.
                            __('dates.hour_emojy.'.(($arr['H'][$i+$j]-1)%12+1).':'.$arr['I'][$i+$j]).' '.(($arr['H'][$i+$j]-1)%12+1).':'.$arr['I'][$i+$j].' '.__('dates.hours.'.$arr['H'][$i+$j])] = [
                                'callback_data'=>$mode.'|Y'.$arr['Y'][$i+$j].'|M'.$arr['M'][$i+$j].'|D'.$arr['D'][$i+$j].'|W'.$arr['W'][$i+$j].'|H'.$arr['H'][$i+$j].'|I'.$arr['I'][$i+$j]
                            ];
                        }
                    }
                    if(!empty($tmp)) $ret['items'][] =  $tmp;
                }
                break;
        }

        $tmp=[];
        $att=[];
        switch($mode){
            case 'MonYear':// اردیبهشت ۱۳۹۷
                $tmp[__('composers_bots.buttons.next_page')] = ['callback_data'=>'next_page','condition'=>($page < 3)];
                $tmp[__('composers_bots.buttons.prev_page')] = ['callback_data'=>'prev_page','condition'=>($page > 1)];
                $ret['navs'][] = $tmp; $tmp=[];
                break;
            //-----------------------
            case 'Day':// سه شنبه، ۱۴ اردیبهشت-  امروز-  فردا، ۱۵ اردیبهشت
                $att[1]['Y'] = $arr['Y'][0];
                $att[1]['M'] = $arr['M'][0]+1;
                if($att[1]['M']>12){
                    $att[1]['Y']++;
                    $att[1]['M'] = 1;
                }
                $att[0]['Y'] = $arr['Y'][0];
                $att[0]['M'] = $arr['M'][0]-1;
                if($att[0]['M']<1){
                    $att[0]['Y']--;
                    $att[0]['M'] = 12;
                }

                $tmp[__('composers_bots.buttons.next_page')] = ['callback_data'=>'next_page'
                    ,'condition'=>(count($arr[$YMDHIS])>$page*$itemRowsPerPage*$colsPerRow)];
                $tmp[__('composers_bots.buttons.prev_page')] = ['callback_data'=>'prev_page'
                    ,'condition'=>($page > 1)];
                $ret['navs'][] = $tmp; $tmp=[];

                $tmp[__('composers_bots.buttons.select_mon')] = ['callback_data'=>'select_mon'];
                $tmp[__('composers_bots.buttons.next_mon',['month'=>__('dates.months.'.$att[1]['M']).' '.$att[1]['Y']])] = ['callback_data'=>'next_mon|Y'.$att[1]['Y'].'|M'.$att[1]['M']
                    ,'condition'=>($att[1]['Y']<=$fromPoint[0]+2)];
                $tmp[__('composers_bots.buttons.prev_mon',['month'=>__('dates.months.'.$att[0]['M']).' '.$att[0]['Y']])] = ['callback_data'=>'prev_mon|Y'.$att[0]['Y'].'|M'.$att[0]['M']
                    ,'condition'=>(($att[0]['Y']>$fromPoint[0])||($att[0]['Y']==$fromPoint[0] && $att[0]['M']>=$fromPoint[1])) ];
                $ret['navs'][] = $tmp; $tmp=[];
                break;
            //-----------------------
            case 'HourMin': // ۱۲:۴۵ ظهر
                $tmp[__('composers_bots.buttons.next_page')] = ['callback_data'=>'next_page','condition'=>(count($arr[$YMDHIS])>$page*$itemRowsPerPage*$colsPerRow)];
                $tmp[__('composers_bots.buttons.prev_page')] = ['callback_data'=>'prev_page','condition'=>($page > 1)];
                $ret['navs'][] = $tmp; $tmp=[];

                $tmp[__('composers_bots.buttons.select_day')] = ['callback_data'=>'select_day'];
                $tmp[__('composers_bots.buttons.select_mon')] = ['callback_data'=>'select_mon'];
                $ret['navs'][] = $tmp; $tmp=[];
                break;
        }

        $ret['page'] = $page;

        return $ret;
    }

//------------------------------------------------------------------------------
    public function sendPhoto($chat_id,$caption,$src=null,$html_mark=null,$forward_chat_id=null){
        if(!$this->operable) return;

        $tmp = [
            'chat_id' => $chat_id,
            'photo'=>$src,
        ];

        if(!is_null($caption)){$tmp['caption'] = $caption;}

        $tmp['disable_notification'] = false;
        if($html_mark) $tmp['parse_mode'] = $html_mark;


        $this->TelegramSendJob( "sendPhoto", $tmp, $forward_chat_id );
    }

//------------------------------------------------------------------------------
    public function sendFile($chat_id,$caption,$type,$src=null,$html_mark=null,$forward_chat_id=null){
        if(!$this->operable) return;

        $available_types = [
            'Photo',
            'MediaGroup',

            'Audio',
            'Document',
            'Voice',

            'Video',
            'Animation',

            'VideoNote',
            'Location',
        ];

        if(!in_array(ucwords($type), $available_types)) {
            mylog('Telegram | Send Wrong File','error','SendFileTelegram :: Wrong Type | '.$type);
            return false;
        }

        $tmp = [
            'chat_id' => $chat_id,
        ];
        $tmp[strtolower($type)] = $src;

//        switch($type){
//            case 'video':
//                $tmp['video'] = $src;
//                break;
//            case 'animation':
//                $tmp['animation'] = $src;
//                break;
//            case 'document':
//                $tmp['document'] = $src;
//                break;
//            case 'audio':
//                $tmp['audio'] = $src;
//                break;
//        }

        if(!is_null($caption)){$tmp['caption'] = $caption;}

        $tmp['disable_notification'] = false;
        if($html_mark) $tmp['parse_mode'] = $html_mark;


        $this->TelegramSendJob( "send".ucwords($type), $tmp, $forward_chat_id );
    }

//------------------------------------------------------------------------------
    public function getFile($chat_id, $file_id){
        if(!$this->operable) return;
        return TelegramPost::getFileRequest($this->bot, $file_id);
    }

//------------------------------------------------------------------------------
    public function getPhoto($chat_id, $file_id){
        if(!$this->operable) return;
        return TelegramPost::getPhotoRequest($this->bot, $file_id);
    }

//------------------------------------------------------------------------------
    public function sendVideo($chat_id,$caption,$src=null,$thumb_src=null,$html_mark='HTML',$deleteLast = false){
        if(!$this->operable) return;
//        if($deleteLast) $this->deleteLastMessage($chat_id,$reply_id);
        $tmp = [
            'chat_id' => $chat_id,
            'video'=>$src,
        ];

        if(!is_null($thumb_src)){$tmp['thumb'] = $thumb_src;}
        if(!is_null($caption)){$tmp['caption'] = $caption;}

        $tmp['disable_notification'] = false;
//        $tmp['supports_streaming'] = false;
        if($html_mark) $tmp['parse_mode'] = $html_mark;

        $this->TelegramSendJob( "sendVideo", $tmp );
    }

//------------------------------------------------------------------------------
    public function getReplyType($reply){//https://core.telegram.org/bots/api#getUpdates
        if(!$this->operable) return;
        $types = [
            'message',
            'edited_message',
            'channel_post',
            'edited_channel_post',
            'inline_query',
            'chosen_inline_result',
            'callback_query',
            'shipping_query',
            'pre_checkout_query'];

        foreach($types as $type)
            if(isset($reply[$type])) return $type;

        return null;
    }
    public function getReplySubtype($reply, $type){//https://core.telegram.org/bots/api#message
        if(!$this->operable) return;
        $subtypes = [
            'text',
            'photo',
            'audio',
            'video',
            'voice',
            'contact',
            'location',
            'venue'];

        if(is_null($type)) return null;

        foreach($subtypes as $item)
            if(isset($reply[$type][$item])) return $item;

        return null;
    }

//------------------------------------------------------------------------------
    public function TelegramSendJob($method, $params, $forward_chat_id = null)
    {
        if(!$this->operable) return;
        dispatch(new TelegramSend($this->bot, $method, $params, null, $forward_chat_id));
    }

}
