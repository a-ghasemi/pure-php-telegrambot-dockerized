<?php

//function decShamsi($sdate,$format,$dec=1,$dec_type="D"){
//    $sdate = dateStrToArray($sdate,$format);
//    $max = [0,31,31,31,31,31,31,30,30,30,30,30,29];
//
//    switch($dec_type){
//        case 'D':
//            $sdate['d']-=$dec;
//            if($sdate['d']<1){
//                $sdate['d'] = $sdate['d']%7
//            }
//        break;
//    }
//
//    return dateArrayToStr($sdate, $format);
//}

#TODO: storage uses filesystems now, no need to make path
# check where this function used and replace with storage function
# then remove this
function attachmentUrl($attachment, $configName = 'filesystems.public_disks')
{
    return asset('storage/' . config($configName)[$attachment->type->folder] . '/' . $attachment->file_name);
}

function count_words($sentence){
//    return mb_str_word_count($sentence); // seems has some wrongs
    $sentence = str_replace('</',' </',$sentence);
    $sentence = strip_tags($sentence);
    $matches = null;
    preg_match_all('/\S+/',$sentence,$matches);
    return count(array_shift($matches));
}

function makeAnyPermissionString($general_permission, $separator = '|'){
 return str_replace('$$$',$general_permission,"$$$.all{$separator}$$$.branch{$separator}$$$.own");
}

function makeAnyPermissionArray($general_permission):array{
 return explode('|',makeAnyPermissionString($general_permission));
}

function runShell($cmd, $stderr = false, $simulate = false)
{
    if($simulate) {
        return $stderr? ['output' => $cmd, 'errors' => '']:$cmd;
    }

    if (!function_exists('proc_open')) die('Error code: 20023');

    $descriptorspec = array(
        0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
        1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
        2 => $stderr ? array("pipe", "w") : array("file", storage_path("logs/shell-output.txt"), "a") // stderr
    );

    $pipes = null;
    $process = proc_open($cmd, $descriptorspec, $pipes);

    if (is_resource($process)) {
        $ret = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        if($stderr){
            $err = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
        }

        proc_close($process);
        return $stderr ? ['output' => $ret, 'errors' => $err] : $ret;
    }

    return false;
}

function getModels($path = null){
    if(is_null($path)) $path = app_path('Models');

    $out = [];
    $results = scandir($path);

    foreach ($results as $result) {
        if (substr($result, -4) !== '.php') continue;
        $out[] = substr($result,0,-4);
    }
    return $out;
}

function model2class($model_name, $namespace = 'App\\Models\\')
{
    $ret = \Illuminate\Support\Str::singular($model_name);
    $ret = \Illuminate\Support\Str::camel($ret);
    $ret = \Illuminate\Support\Str::ucfirst($ret);
    return $namespace . $ret;
}

function model2route($model_name)
{
    $ret = \Illuminate\Support\Str::plural($model_name);
    $ret = \Illuminate\Support\Str::snake($ret);
    return $ret;
}

function model2table($model_name)
{
    $ret = \Illuminate\Support\Str::plural($model_name);
    $ret = \Illuminate\Support\Str::snake($ret);
    return $ret;
}

function table2model($table_name)
{
    $ret = \Illuminate\Support\Str::singular($table_name);
    $ret = \Illuminate\Support\Str::camel($ret);
//    $ret = str_replace('_',' ',$ret);
    $ret = \Illuminate\Support\Str::ucfirst($ret);
    $ret = str_replace('_','',$ret);
    return $ret;
}

function modelObj($model_name, $namespace = 'App\\Models\\')
{
    $modelClass = model2class($model_name, $namespace);
    return new $modelClass;
}

function config_keys_all($config)
{
    return array_keys(config($config));
}

function config_trans($config, $key)
{
    return trans("{$config}.".config("{$config}.{$key}"));
}

function findKey($tab, $key)
{
    foreach ($tab as $k => $value) {
        if ($k == $key) return $value;
        if (is_array($value)) {
            $find = findKey($value, $key);
            if ($find) return $find;
        }
    }
    return null;
}

function chShamsi($sdate, $format, $ch = null)
{
    $jd = (is_array($sdate)) ? $sdate : dateStrToArray($sdate, $format);
    $gd = $jd;
    $gd2 = \App\Libraries\jDateTime::toGregorian($jd['Y'], $jd['m'], $jd['d'], true);
    foreach (['Y', 'm', 'd'] as $i) {
        $gd[$i] = $gd2[$i];
    }
    $gd3 = $gd;
    foreach (['Y', 'm', 'd'] as $i) {
        unset($gd3[$i]);
    }
    $gd = new DateTime(trim($gd['Y'] . '-' . $gd['m'] . '-' . $gd['d'] . ' ' . dateArrayToStr($gd3, 'H:i')));
    if (!is_null($ch)) $gd->modify($ch);
    $gd = $gd->format('Y-m-d H:i:s');

    $gd = dateStrToArray($gd, 'Y-m-d H:i:s');
    $jd2 = \App\Libraries\jDateTime::toJalali($gd['Y'], $gd['m'], $gd['d'], true);
    foreach (['Y', 'm', 'd'] as $i) if (isset($jd2[$i])) $jd[$i] = $jd2[$i];
    foreach (['H', 'i', 's'] as $i) if (isset($gd[$i])) $jd[$i] = $gd[$i];

//    $jd = is_array($sdate)?$jd:dateArrayToStr($jd,$format);
    $jd = dateArrayToStr($jd, $format);
    return $jd;
}

function dex()
{
    ob_start();
    array_map(function ($x) {
        Symfony\Component\VarDumper\VarDumper::dump($x);
    }, func_get_args());
    return ob_get_clean();
}

/*
 * @param HW width | height | null = both as array
 */
function getTextSize($text, $fontSize, $lang, $HW = null, $fontFile = null)
{
    if (is_null($fontFile)) {
        $fontFile = ($lang == 'fa') ? public_path('vendor/fonts/ISans/ISansWeb.ttf') : public_path('vendor/truetypefonts/roboto/Roboto-Bold.ttf');
    }
    $font = new Intervention\Image\Imagick\Font(
        ($lang == 'fa') ? image_persian_text($text) : $text
    );
    $font->valign('top');
    $font->file($fontFile);
    $font->size($fontSize);
    $ret = $font->getBoxSize();
    return (is_null($HW) ? $ret : $ret[$HW]);
}

function get_best_fit($text, $max_width)
{
//$m1=microtime(true);
    $cnt = 15;
    $lim = [10, min($max_width, 450)];
    $size1 = 0;
    $size2 = ceil(($lim[0] + $lim[1]) / 2);

    $exit = false;
    while (!$exit && $cnt > 0 && $lim[0] < $lim[1]) {
        $size1 = $size2;
        $tmpw = getTextSize($text, $size1, 'fa', 'width');
//dump([$cnt,$size2,$lim,$tmpw , $max_width]);
        $cnt -= ($tmpw > $max_width) ? 0 : 1;
        $size2 = ($tmpw > $max_width) ? ceil(($lim[0] + $size1) / 2) : ceil(($lim[1] + $size1) / 2);
        $lim[1] = ($tmpw > $max_width) ? $size1 : $lim[1];
        $lim[0] = ($tmpw < $max_width) ? $size1 : $lim[0];

        if ($lim[1] - $lim[0] < 5) {
            for ($i = $lim[1]; $i >= $lim[0]; $i--) {
                $tmpw = getTextSize($text, $i, 'fa', 'width');
                if ($tmpw <= $max_width) {
                    $size1 = $i;
                    $exit = true;
                    break;
                }
            }

        }
    }
//$m2=microtime(true);
//dd([$size1,getTextSize($text,$size1,'fa','width'),$max_width,(isset($m1)?$m2-$m1:null)]);
    return $size1;
}

function set_text_persian(&$img, $pos_x, $pos_y, $size, $color, $content, $rotate = 0)
{
    $ret = null;
    $img->text(image_persian_text($content), $pos_x, $pos_y,
        function ($font) use ($size, $color, $rotate, &$ret) {
            $font->file(public_path('vendor/fonts/ISans/ISansWeb.ttf'));
//                $font->file(public_path('vendor/truetypefonts/irasans.ttf'));
//                $font->file(public_path('vendor/truetypefonts/BHoma.ttf'));
            $font->color($color);
            $font->align('right');
            $font->valign('top');
            $font->size($size);
            if ($rotate > 0) {
                $font->angle($rotate);
            }
            $ret = $font->getBoxSize();
        });
    return $ret;
}

function set_text_english(&$img, $pos_x, $pos_y, $size, $color, $content, $rotate = 0)
{
    $ret = null;
    $img->text($content, $pos_x, $pos_y,
        function ($font) use ($size, $color, $rotate, &$ret) {
            $font->file(public_path('vendor/truetypefonts/roboto/Roboto-Bold.ttf'));
            $font->size($size);
            $font->color($color);
            $font->align('left');
            $font->valign('top');
            if ($rotate > 0) $font->angle($rotate);
            $ret = $font->getBoxSize();
        });
    return $ret;
}

function alertAdminTelegram($message, $commands = [], $justSysadmin = true, $html_mark = 'HTML')
{
    $chat_op = new App\Support\Telegram\TelegramBotOperator(1);
    $admins = ($justSysadmin) ? \App\Models\User::hasAnyRoles('sysadmin@web','sysadmin@api')->get()
        : \App\Models\User::hasAnyRoles('sysadmin@web','sysadmin@api','admin@web','admin@api')->get();

    foreach ($admins as $admin) {
        if ($admin->telegram_uid) $chat_op->sendMessageInlineMarkup($admin->telegram_uid, trim($message), $commands, $html_mark, 1);
    }
}

function alertAdminTelegramImage($message, $image, $commands = [], $justSysadmin = true, $html_mark = 'HTML')
{
    $chat_op = new \App\Http\Controllers\System\TelegramBots\Bases\BaseTelegramOperator(1);
    $admins = ($justSysadmin) ? \App\Models\User::where('group_id', 1)->get() : \App\Models\User::whereIn('group_id', [1,
        2])->get();

    foreach ($admins as $admin) {
        if ($admin->telegram_uid) $chat_op->sendPhoto($admin->telegram_uid, trim($message), $image, $html_mark);
//        $chat_op->sendPhoto($admin->telegram_uid,trim($message),$image,$commands);
    }
}

function alertAdminTelegramFile($type, $message, $file, $commands = [], $justSysadmin = true, $html_mark = 'HTML')
{
    $chat_op = new \App\Http\Controllers\System\TelegramBots\Bases\BaseTelegramOperator(1);
    $admins = ($justSysadmin) ? \App\Models\User::where('group_id', 1)->get() : \App\Models\User::whereIn('group_id', [1,
        2])->get();

    foreach ($admins as $admin) {
        if ($admin->telegram_uid) $chat_op->sendFile($admin->telegram_uid, trim($message), $type, $file, $html_mark);
    }
}

function dateStrToArray($inp, $format)
{
    $format = preg_split('/[\/|\-: ]/', $format);
    $inp = preg_split('/[\/|\-: ]/', $inp);
    $tmp = [];
    foreach ($inp as $i => $d)
        if (isset($format[$i])) $tmp[$format[$i]] = $d;
    $inp = $tmp;
    $inp = array_map('intval', $inp);
    return $inp;
}

function dateArrayToStr($inp, $format)
{
    $signs = ['Y' => 4, 'y' => 4, 'm' => 2, 'M' => 2, 'd' => 2, 'D' => 2, 'H' => 2, 'i' => 2, 'I' => 2, 's' => 2,
              'S' => 2, 'w' => 1, 'W' => 1];
    foreach ($signs as $i => $l)
        if (isset($inp[$i]))
            $format = str_replace($i, substr(str_repeat('0', $l) . $inp[$i], -1 * $l), $format);
    return $format;
}

function compareDates($date1, $date2 = null, $format = 'Y-m-d H:i:s')
{
    if (is_null($date2)) {
        $date2 = \App\Libraries\jDateTime::date('Y|m|d|w|H|i', time(), false);
    }
//    $format = explode('|',$format);
    $format = preg_split('/[\/|\-: ]/', $format);
    if (!is_array($date1)) {
//        $date1 = explode('|',$date1);
        $date1 = preg_split('/[\/|\-: ]/', $date1);
        $tmp = [];
        foreach ($date1 as $i => $d)
            if (isset($format[$i])) $tmp[$format[$i]] = $d;
        $date1 = $tmp;
    }
    $date1 = array_map('intval', $date1);
    if (!is_array($date2)) {
//        $date2 = explode('|',$date2);
        $date2 = preg_split('/[\/|\-: ]/', $date2);
        $tmp = [];
        foreach ($date2 as $i => $d)
            if (isset($format[$i])) $tmp[$format[$i]] = $d;
        $date2 = $tmp;
    }
    $date2 = array_map('intval', $date2);

    $orders = ['Y', 'm', 'M', 'd', 'D', 'H', 'h', 'i', 'I', 's', 'S'];
    foreach ($orders as $ord) {
        if (!isset($date1[$ord]) && !isset($date2[$ord])) continue;
        if (!isset($date1[$ord])) return 1;
        if (!isset($date2[$ord])) return -1;
        if ($date1[$ord] > $date2[$ord]) return -1;
        if ($date1[$ord] < $date2[$ord]) return 1;
    }
    return 0;
}

function array_map_deep($array, $callback)
{
    $new = array();
    if (is_array($array))
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $new[$key] = array_map_deep($val, $callback);
            } else {
                $new[$key] = call_user_func($callback, $val);
            }
        } else
        $new = call_user_func($callback, $array);
    return $new;
}

function setAlert($alert)
{
    Session::push('alert', $alert);
}

function getlast($str, $chr)
{
    $arr = explode($chr, $str);
    return end($arr);
}

function strMultiple($prefix, $str, $separator)
{
    $arr = explode($separator, $str);
    foreach ($arr as &$ar) $ar = $prefix . $ar;
    return implode($separator, $arr);
}

function randStr($len = 3, $flags = [1, 1, 1, 0], $round = false)
{

    $str_bigChars = "ABCDEFGHJKLMNPQRSTUVWXYZ";
    $str_smallChars = "abcdefghijkmnopqrstuvwxyz";
    $str_digits = $round ? "12346789" : "123456789";
    $str_signs = "~!@#$%^&*()";

    $str = "";
    if ($flags[0]) $str .= $str_bigChars;
    if ($flags[1]) $str .= $str_smallChars;
    if ($flags[2]) $str .= $str_digits;
    if ($flags[3]) $str .= $str_signs;

    $ret = substr(str_shuffle($str), 0, $len);
    $t = rand(1, 100);
    if ($round) {
        $tmp = '';
        for ($i = 0; $i < strlen($ret); $i++) {
            $tmp .= (($i + 1) % 2 == 0) ? (($t++ % 2) ? '0' : '5') : $ret[$i];
        }
        $ret = $tmp;
    }
    return $ret;
}

function dig_fa2en($inp)
{
    $out = null;

    if (is_array($inp)) {
        $out = [];
        foreach ($inp as $i => $v)
            $out[$i] = dig_fa2en($v);
    } else {
        $changes = [
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
        ];
        $out = strtr($inp, $changes);
    }

    return $out;
}

function getFormedError($e, $title = null, $logIt = true)
{
    $ret =
//            'Type: ' . $e->getFile() . ' | ' .
        'File: ' . $e->getFile() . ' | ' .
        'Line: ' . $e->getLine() . ' | ' .
        'Msg: ' . $e->getMessage();
    if ($logIt)
        mylog($title, 'error', $ret);
    return $ret;
}

function getArrayError($e)
{
    $ret = [
        'File' => $e->getFile(),
        'Line' => $e->getLine(),
        'Msg'  => $e->getMessage(),
    ];
    mylog(null, 'error', getFormedError($e, false));
    return $ret;
}

function datetostr($dt)
{
    return GregToJalali('Y/m/d', 'Y-m-d', $dt->format('Y-m-d'));
}

function datetimetostr($dt)
{
    return GregToJalali('Y/m/d H:i:s', 'Y-m-d H:i:s', $dt->format('Y-m-d H:i:s'));
}

function getVal(&$var, $default = '', $trim = true)
{
    if (!isset($var) || empty($var) || is_null($var))
        $var = $default;
    if ($trim) {
        if (is_array($var))
            $var = array_map('trim', $var);
        elseif (!is_bool($var))
            $var = trim($var);
    }
    return $var;
}

function getDVal(&$arr1, $index1, &$arr2, $index2, $default = '', $trim = true)
{
    if (!isset($arr1) || empty($arr1) || is_null($arr1) || !is_array($arr1)) {
        if (!isset($arr2) || empty($arr2) || is_null($arr2) || !is_array($arr2))
            $var = $default;
        else
            try {
                $var = $arr2;
                foreach (explode('|', $index2) as $ind) $var = $var[$ind];
            } catch (Exception $e) {
                $var = $default;
            }
    } else {
        try {
            $var = $arr1;
            foreach (explode('|', $index1) as $ind) $var = $var[$ind];
        } catch (Exception $e) {
            $var = $default;
        }
    }

    if ($trim) {
        if (is_array($var))
            $var = array_map('trim', $var);
        elseif (!is_bool($var))
            $var = trim($var);
    }
    return $var;
}

function getMinimumCost($dbCosts, $die = false)
{
    if (empty($dbCosts)) return 0;
    $ret = [999999999999999, ''];
    $costList = [];
    foreach ($dbCosts as $i => $items)
        foreach ($items as $item)
            if (!empty($item) && isset($item['room1'])) {
                $coding = Category::getCodingByCatId($item['currency']);
                if (is_null($coding)) continue;
                if (isset($costList[$i])) {
                    $costList[$i][0] = $costList[$i][0] + justVal($coding['input01'], 1) * exportVal($item['room1']);
                    $costList[$i][1] = $costList[$i][1] . ' + ' . $item['room1'] . ' ' . Category::getNameByCatId($item['currency']);
                } else {
                    $costList[$i][0] = justVal($coding['input01'], 1) * exportVal($item['room1']);
                    $costList[$i][1] = number_format(intval($item['room1'])) . ' ' . Category::getNameByCatId($item['currency']);
                }
            }

    if (empty($costList)) return '----';
    foreach ($costList as $item)
        if (intval($item[0]) < $ret[0]) $ret = $item;

    return $ret[1];
}

function toAscii($str)
{
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $num = range(0, 9);
    $ret = str_replace($persian, $num, $str);
    $ret = str_replace($arabic, $num, $ret);
    return $ret;
}

function isGregLeapYear($year)
{
    return ((($year % 4 == 0) && ($year % 100 != 0)) || ($year % 400 == 0) ? 1 : 0);
}

//function getLangFiles($trans) {
//    $ret = [];
//
//    $files = [];
//    $dir = app_path() . '/lang/' . $trans . '/';
//
//    if (!is_dir($dir))
//        return false;
//
//    if ($dh = opendir($dir)) {
//        while (($file = readdir($dh)) !== false)
//            if (!in_array($file, ['.', '..']))
//                $files[] = $file;
//
//        closedir($dh);
//    }
//
//    foreach ($files as &$file) {
//        unset($transes);
//        unset($bases);
//        include $dir . $file;
//        $file = str_replace('.php', '', $file);
//        foreach ($transes as $i => $trans)
//            foreach ($trans as $k => $v)
//                if (!empty($bases))
//                    $ret[$file][$bases[$i]][str_replace($bases[$i], '', $k)] = $v;
//                else
//                    $ret[$file][0][$k] = $v;
//    }
//
//    return ['files' => $files, 'transes' => $ret];
//}

function sameness($a, $b)
{
    $result = '';
    $len = strlen($a) > strlen($b) ? strlen($b) : strlen($a);
    for ($i = 0; $i < $len; $i++) {
        if (substr($a, $i, 1) == substr($b, $i, 1)) {
            $result .= substr($a, $i, 1);
        } else {
            break;
        }
    }
    return $result;
}

function mylog($title, $type, $message, $addFileFlag = true)
{
    if (!\Illuminate\Support\Facades\Schema::hasTable('logs')) return;
    $log_message = $message;
    if ($addFileFlag) {
        $bt = debug_backtrace(1);
        $caller = array_shift($bt);

        $log_message =
            '<b>File:</b> ' . $caller['file']
            . "\n<b>Line:</b> " . $caller['line']
            . "\n<b>Cast:</b> " . (is_array($message) ? 'Array' : (is_object($message) ? 'Object' : (is_null($message) ? 'Null Data' : 'Raw Data')))
            . "\n<b>Message Length:</b> " . (is_countable($message) ? count($message) : strlen($message))
            . "\n<b>Message:</b> " . (is_array($message) || is_object($message) ? dex($message) : $message);
    }
    $log = \App\Models\Log::create([
        'title'    => $title ?? '__ Untitled __',
        'user_id'  => null,
        'ip'       => null,
        'type'     => $type,
        'message'  => $log_message,
        'curr_url' => null,
        'prev_url' => null,
        'mili'     => null,
    ]);

    if (in_array(strtolower($log->type), ['ding', 'error', 'hack', 'attack'])) {
        alertAdminTelegram(
            '⚠ <b>' . ucwords($log->type) . ' @ ' . config('app.telegram_title', '') . '</b>'
            . "\n" . "<b>TITLE :</b> " . $log->title
            . "\n" . "<b>IP :</b> " . ($log->ip ?? '---')
            . "\n" . "<b>Date :</b> " . getFormedDate($log->created_at)
            . "\n" . "<b>Time :</b> " . (!isset($log->created_at) ? '---' : $log->created_at->format('h:i:s A'))
            . "\n" . "<b>User :</b> " . ($log->user ? $log->user->username : '---')
            . "\n" . "<b>CURR URL :</b> " . str_replace(url('/'), '', $log->curr_url)
            . "\n" . "<b>PREV URL :</b> " . str_replace(url('/'), '', $log->prev_url)
            . "\n" . "<b>TrackFile :</b> " . ($addFileFlag?'Yes':'No')
            . "\n===================="
            . "\n" . $log->message
            , [
                'Show Log' => ['url' => route('admin.logs.show', [$log->id])],
            ]
            , true
        );

    }

    return $log;
}

function getFormedDatePr($date)
{
    $ret = !isset($log->created_at) ?
        '---' :
        \App\Libraries\jDateTime::convertFormatToFormat('Y / m / d', 'Y-m-d', $log->created_at->format('Y-m-d'));
    $ret .= (!isset($log->created_at) ? '' : ' ' . daynameEn2Fa($log->created_at->format('D')));

    return $ret;
}

function getFormedDate(\Carbon\Carbon $date)
{
    $ret = !isset($date) ? '---' : $date->format('Y-m-d D');
    return $ret;
}

function quickLog($message)
{
    return mylog(null, 'quick', $message);
}

function fileIdToUrl(&$var)
{
    $tmp = $var;
    unset($var);
    $var = [];
    $var['recid'] = $tmp;
    $tmp = Files::getUrl($tmp);
    $var['url'] = (!empty($tmp)) ? assetlink($tmp) : null;
    return $var;
}

function rTypeToText($typeName)
{
    $states = ['0' => 'نامشخص', 'Tour' => 'تور', 'News' => 'خبر', 'Logbook' => 'سفرنامه'];
    return ((array_has($states, $typeName)) ? $states[$typeName] : $states[0]);
}

function toursState($stateCode)
{
    $states = ['draft'   => 'پیش نویس', 'waiting' => 'منتظر تایید', 'onair' => 'در حال نمایش',
               'expired' => 'منقضی شده', 'suspended' => 'معلق', 0 => 'نامشخص'];
    return ((array_has($states, $stateCode)) ? $states[$stateCode] : $states[0]);
}

function terminalState($stateCode)
{
    $states = ['website'     => 'سایت', 'mobile' => 'موبایل', 'android' => 'اندروید', 'ios' => 'ios',
               'app-android' => 'اندروید', 'app-ios' => 'ios', 0 => 'نامشخص'];
    return ((array_has($states, $stateCode)) ? $states[$stateCode] : $states[0]);
}

function makeTourUrl($id, $title)
{
    return trim($id) . '-' . makeFreshUrl($title);
}

function makeFreshTitle($title)
{
    return ucwords(preg_replace('/[ +,_|.-?!]/', ' ', strtolower(trim($title))));
}

function makeFreshUrl($title)
{
    return preg_replace('/[ +,_|.]/', '-', strtolower(trim($title)));
}

// input format must be : Y/m/d
function formatDate($slash_date)
{
    $persianMonth = ['',
        'فروردین', 'اردیبهشت', 'خرداد',
        'تیر', 'مرداد', 'شهریور',
        'مهر', 'آبان', 'آذر',
        'دی', 'بهمن', 'اسفند',
    ];
    try {
        $dateT = explode('/', toAscii($slash_date));
        $dateT = array_map('intval', $dateT);
        $slash_date = $dateT[2] . ' ' . $persianMonth[$dateT[1]] . ' ' . substr($dateT[0], 2);
    } catch (Exception $e) {
    }
    return $slash_date;
}

function strAddChBef($inp, $ch, $count)
{
    return substr(str_repeat($ch, $count) . $inp, -1 * $count);
}

function formatTime($colon_time)
{
    try {
        $timeT = explode(':', toAscii($colon_time));
        $timeT = array_map('intval', $timeT);
        $colon_time = substr('00' . $timeT[0], -2) . ':' . substr('00' . $timeT[1], -2);
    } catch (Exception $e) {
    }
    return $colon_time;
}

function getDayName($date)
{
    require_once dirname(__FILE__) . '/jdatetime.class.php';
    $persianDays = ['', 'دوشنبه', 'سه شنبه', 'چهارشنبه', 'پنج شنبه', 'جمعه', 'شنبه', 'یک شنبه'];
    try {
        $dateT = explode('/', $date);
        $dateT = array_map('intval', $dateT);
        $dateG = jDateTime::toGregorian($dateT[0], $dateT[1], $dateT[2]);
        $dateN = date("N", strtotime($dateG[0] . '/' . $dateG[1] . '/' . $dateG[2]));
        $date = $persianDays[$dateN];
    } catch (Exception $e) {
    }
    return $date;
}

function GregToJalali($jalaliFormat, $gregFormat, $dateString)
{
    require_once dirname(__FILE__) . '/jdatetime.class.php';
    $dateString = toAscii($dateString);
    try {
        $date = (empty($dateString)) ? '' : jdatetime::convertFormatToFormat($jalaliFormat, $gregFormat, $dateString);
    } catch (Exception $e) {
        $date = '';
    }

    return toAscii($date);
}

function makeZFCatId($catid)
{
    $catsId = explode('.', $catid);
    foreach ($catsId as &$id) $id = strrev(substr(strrev("0000000000" . $id), 0, 10));
    return implode('.', $catsId);
}

function createPaging($recordsCount, $recordPerPage, $currentPageIndex = 1, $beforeRange = 3, $afterRange = 6)
{
    $total = ceil(intval($recordsCount) / intval($recordPerPage));
    $ret['activeId'] = min($currentPageIndex, $total);

    $ret['base'] = max(1, $ret['activeId'] - $beforeRange);
    $ret['PrevPage'] = (($ret['base'] == 1) ? null : ($ret['activeId'] - 1));

    $ret['last'] = min($total, $ret['activeId'] + $afterRange);
    $ret['NextPage'] = (($ret['last'] == $total) ? null : ($ret['activeId'] + 1));

    return $ret;
}

function getRequestSegments()
{
    $i = 1;
    $parameters = [];
    while ($parameters[] = Request::segment($i)) $i++;
    array_pop($parameters);
    return $parameters;
}

function justVal($var, $def = -1)
{
    if (is_numeric($var)) return intval($var); else return $def;
}

function exportVal(&$var)
{
    if (!isset($var)) return 0;
    return preg_replace("/\\D.*?/", "", $var);
}

function convertField($modelname, $record, $item)
{
    if (preg_match("/MDL::\\[([\\w|\\.].*?)\\]\\{([\\w|\\.].*?)\\}\\[([\\w|\\.].*?)\\]\\[([\\w|\\.].*?)\\]/", $item, $match)) // Query to another Model
    {
        $model = $match[2];
        $row = $model::where('deleted', '!=', true)->where('hide', '!=', true)->Where(function ($query) use ($match, $record) {
            $query->Where($match[3], $record[$match[1]])
                ->orWhere($match[3], intVal($record[$match[1]]));
        })->get([$match[4]])->toArray();
        $tmp = '';
        if (!empty($row))
            foreach ($row as $irow) {
                $tmatchs = explode('.', $match[4]);
                $zrow = $irow;
                foreach ($tmatchs as $tmatch) $zrow = $zrow[$tmatch];
                $tmp .= $zrow . (($irow !== last($row)) ? ', ' : '');
            }

        return $tmp;
    } elseif (preg_match("/MDL2::\\[([\\w|\\.].*?)\\]<(.*?)>/", $item, $match)) // Query to another Model
    {
        $details = explode(',', $match[2]);
        $rows = null;
        foreach ($details as $detail) {
            if (preg_match("/\\{(\\w.*?)\\}\\[([\\w|\\.|\\|].*?)]\\[([\\w|\\.].*?)]/", $detail, $preg)) {
                $model = $preg[1];
                $field = explode('|', $preg[2]);
                $rowx = $model::where('deleted', '!=', true)->where('hide', '!=', true);

                if ($field[0] == 'INT') $rowx = $rowx->Where($field[1], justVal($record[$match[1]]));
                else $rowx = $rowx->Where($field[1], $record[$match[1]]);

                $rowx = $rowx->get([$preg[3]])->toArray();
                $rows[$model]['data'] = $rowx;
                $rows[$model]['field'] = $preg[3];
            }
        }

        $tmp = '';
        foreach ($rows as $row)
            if (!empty($row['data']))
                foreach ($row['data'] as $irow) {
                    $tmatchs = explode('.', $row['field']);
                    $zrow = $irow;
                    foreach ($tmatchs as $tmatch) {
                        $zrow = $zrow[$tmatch];
                    }
                    $tmp .= $zrow . (($irow !== last($row['data'])) ? ', ' : '');
                }
        return $tmp;
    } elseif (preg_match("/CFN::\\[([\\w|\\.].*?)\\]\\{([\\w|\\.].*?)\\}/", $item, $match)) // Send to Current Class Function, like date convertions
    {
        $function = $match[2];
        $row = $modelname::$function($record[$match[1]]);
        $tmp = '';
        if (is_array($row)) {
            if (!empty($row))
                foreach ($row as $irow)
                    $tmp .= $irow . (($irow !== last($row)) ? ', ' : '');
        } else
            $tmp = $row;


        return $tmp;
    } elseif (preg_match("/FN::\\[([\\w|\\.].*?)\\]\\{([\\w|\\.].*?)\\}/", $item, $match)) // Send to Function, like date convertions
    {
        $function = $match[2];
        $row = $function($record[$match[1]]);
        $tmp = '';
        if (is_array($row)) {
            if (!empty($row))
                foreach ($row as $irow)
                    $tmp .= $irow . (($irow !== last($row)) ? ', ' : '');
        } else
            $tmp = $row;


        return $tmp;
    } elseif (preg_match("/([\\w].*?\\(\\))/", $item, $match)) {
        $t = null;
        eval('$t = $record->' . $match[1] . ';');
        return $t;
    } else return $record[$item];

}

function nthsub($string, $needle, $nth)
{
    $max = strlen($string);
    $n = 0;
    for ($i = 0; $i < $max; $i++) {
        if ($string[$i] == $needle) {
            $n++;
            if ($n >= $nth) break;
        }
    }
    return (($n < $nth) ? $string : substr($string, 0, $i));
}

function jsUnserialize($string)
{
    $data = [];
    $tmp = explode('&', $string);
    foreach ($tmp as $dt) {
        $z = explode('=', $dt);
        $data[$z[0]] = trim($z[1]);
    }
    return $data;
}

function getCurrentUserData()
{
    $check = false;
    $data = null;

    if (Auth::staffs()->check()) {
        $check = true;
        $dt['mode'] = 1;
        $usr = Auth::staffs()->get();
        $usr_a = json_decode(json_encode($usr), true);
        $dt['id'] = $usr_a['_id'];
        $dt['url'] = $usr->getPanelUrl();
        $dt['userInfo'] = Staff::getInfo($dt['id']);
        $dt['name'] = $usr_a['info']['fullname'];

        $data[] = $dt;
    }
    if (Auth::agencies()->check()) {
        $check = true;
        $dt['mode'] = 2;
        $usr = Auth::agencies()->get();
        $usr_a = json_decode(json_encode($usr), true);
        $dt['id'] = $usr_a['_id'];
        $dt['url'] = $usr->getPanelUrl();
        $dt['userInfo'] = Agency::getInfo($dt['id']);
        $dt['name'] = $usr_a['names']['nameFa'];

        $data[] = $dt;
    }

    if (Auth::hotels()->check()) {
        $check = true;
        $dt['mode'] = 3;
        $usr = Auth::hotels()->get();
        $usr_a = json_decode(json_encode($usr), true);
        $dt['id'] = $usr_a['_id'];
        $dt['url'] = $usr->getPanelUrl();
        $dt['userInfo'] = Hotel::getInfo($dt['id']);
        $dt['name'] = $usr_a['names']['nameFa'];

        $data[] = $dt;
    }

    if (Auth::members()->check()) {
        $check = true;
        $dt['mode'] = 4;
        $usr = Auth::members()->get();
        $usr_a = json_decode(json_encode($usr), true);
        $dt['id'] = $usr_a['_id'];
        $dt['userInfo'] = User::getInfo($dt['id']);
        $dt['name'] = $usr_a['info']['fullname'];

        $data[] = $dt;
    }


    if (!$check) return false;

    return $data;

}

function str2hex($string)
{
    $hex = '';
    for ($i = 0; $i < strlen($string); $i++) {
        $ord = ord($string[$i]);
        $hexCode = dechex($ord);
        $hex .= substr('0' . $hexCode, -2);
    }
    return strToUpper($hex);
}

function hex2str($hex)
{
    $hex = str_replace('%', '', $hex);
    $string = '';
    for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
        $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
    }
    return $string;
}

if (!function_exists('old')) {
    function old($name, $default)
    {
        return Input::old($name, $default);
    }
}

function makeLowQuality($sourcePath, $destPath, $mod)
{
    $srcimage = null;
    switch (strtolower($mod)) {
        case 'png':
            $srcimage = imagecreatefrompng($sourcePath);
            break;
        case 'jpeg':
        case 'jpg':
            $srcimage = imagecreatefromjpeg($sourcePath);
            break;
        default:
            return false;
    }
    list($width, $height) = getimagesize($sourcePath);
    $img = imagecreatetruecolor($width, $height);
    $bga = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagecolortransparent($img, $bga);
    imagefill($img, 0, 0, $bga);
    imagecopy($img, $srcimage, 0, 0, 0, 0, $width, $height);
    imagetruecolortopalette($img, false, 255);
    imagesavealpha($img, true);
    imagepng($img, $destPath);
    imagedestroy($img);
    return true;
}

function gotoNextId($id, $sepChar = '1')
{
    $tid = (!is_null($id)) ? $id + 1 : 0;
    while (strstr($tid, $sepChar)) $tid++;
    return $tid;
}


//Some other bots (check if you seen in database) : Sogou Spider | Exabot | Soso Spider | MSN Bot
function isGoogleBot($all = false)
{
    return strstr(strtolower(Request::header('User-Agent')), $all ? "google" : "googlebot");
}

function isBingBot($all = false)
{
    return strstr(strtolower(Request::header('User-Agent')), $all ? "bing" : "bingbot");
}

function isYandexBot($all = false)
{
    return strstr(strtolower(Request::header('User-Agent')), $all ? "yandex" : "yandexbot");
}

function isBaiduBot($all = false)
{
    return strstr(strtolower(Request::header('User-Agent')), $all ? "baidu" : "baidubot");
}

function isFacebookBot($all = false)
{
    return strstr(strtolower(Request::header('User-Agent')), $all ? "facebook" : "facebookbot");
}

function isBot()
{
    return strstr(strtolower(Request::header('User-Agent')), "bot");
}

function isAllowBot()
{
    return isGoogleBot(1) || isBingBot(1) || isYandexBot(1) || isBaiduBot(1) || isFacebookBot(1);
}


function pathToUploadedFile($path, $public = false)
{
    $name = File::name($path);
    $extension = File::extension($path);
    $originalName = $name . '.' . $extension;
    $mimeType = File::mimeType($path);
    $size = File::size($path);
    $error = null;
    $object = new \Illuminate\Http\UploadedFile($path, $originalName, $mimeType, $size, $error, $public);
    return $object;
}

function hideEmail($inp)
{
    $inp = explode('@', $inp);
    $inp2 = $inp[0];
    if (strlen($inp[0]) <= 3) $inp[0] = str_repeat('* ', strlen($inp[0]));
    else $inp[0] = str_repeat('* ', rand(max(strlen($inp[0]) - 5, 2), min(strlen($inp[0]), 10)));
    $inp[0][0] = $inp2[0];
    $inp[0][strlen($inp[0]) - 1] = $inp2[strlen($inp2) - 1];

    return $inp[0] . '@' . $inp[1];
}

function trimTags($tags_ids, $sep = '|')
{
    $tags = [];
    $tags_ids_arr = explode($sep, $tags_ids);
    $tags_ids_arr = array_map('intval', $tags_ids_arr);
    foreach ($tags_ids_arr as $i => $t) {
        $tmp = \App\Models\Tag::find($t);
        if (empty($tmp)) {
            unset($tags_ids_arr[$i]);
            continue;
        }
        $tags[] = $tmp;
    }
    $tags_ids = implode($sep, $tags_ids_arr);
    return [$tags_ids, $tags];
}

function trimCategories($cats_ids, $sep = '1')
{
    $cats = [];
    $cats_ids_arr = explode($sep, $cats_ids);
    $cats_ids_arr = array_map('intval', $cats_ids_arr);
//    sort($cats_ids_arr);
    foreach ($cats_ids_arr as $i => $t) {
        $tmp = \App\Models\ProductCategory::find($t);
        if (empty($tmp)) {
            unset($cats_ids_arr[$i]);
            continue;
        }
        $cats[] = $tmp;
    }
    $cats_ids = implode($sep, $cats_ids_arr);
    return [$cats_ids, $cats];
}

function trimShopTypes($cats_ids, $sep = '1')
{
    $cats = [];
    $cats_ids_arr = explode($sep, $cats_ids);
    $cats_ids_arr = array_map('intval', $cats_ids_arr);
    foreach ($cats_ids_arr as $i => $t) {
        $tmp = \App\Models\ShopType::find($t);
        if (empty($tmp)) {
            unset($cats_ids_arr[$i]);
            continue;
        }
        $cats[] = $tmp;
    }
    $cats_ids = implode($sep, $cats_ids_arr);
    return [$cats_ids, $cats];
}

function trimWritingTypes($cats_ids, $sep = '1')
{
    $cats = [];
    $cats_ids_arr = explode($sep, $cats_ids);
    $cats_ids_arr = array_map('intval', $cats_ids_arr);
    foreach ($cats_ids_arr as $i => $t) {
        $tmp = \App\Models\Category::find($t);
        if (empty($tmp)) {
            unset($cats_ids_arr[$i]);
            continue;
        }
        $cats[] = $tmp;
    }
    $cats_ids = implode($sep, $cats_ids_arr);
    return [$cats_ids, $cats];
}

//function assetlink($path, $ssl = null){
function assetlink($path, $version = false)
{
    $ret = (!env('IS_LOCALHOST', true) && env('USE_SSL', false)) ? secure_asset($path) : asset($path);
    if ($version && file_exists($path)) {
        $timestamp = filemtime(public_path($path));
        $ret .= '?v=' . md5(date('YmdHis', $timestamp));
    }
    return $ret;
}

function fa2en($char)
{
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'
        , 'آ', 'ا', 'ء', 'ب', 'پ', 'ت', 'ث', 'ج', 'چ', 'ح', 'خ', 'د', 'ذ', 'ر', 'ز'
        , 'ژ', 'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ک', 'ك', 'گ', 'ل'
        , 'م', 'ن', 'و', 'ه', 'ة', 'ی', 'ي', 'ئ'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'
        , 'a', 'a', 'a', 'b', 'p', 't', 's', 'j', 'ch', 'h', 'kh', 'd', 'z', 'r', 'z'
        , 'zh', 's', 'sh', 's', 'z', 't', 'z', 'a', 'gh', 'f', 'gh', 'k', 'k', 'g', 'l'
        , 'm', 'n', 'v', 'h', 'h', 'y', 'y', 'y'];
    return str_replace($persian, $english, $char);
}

function setLang(&$lang)
{
    if (is_null($lang) || !in_array($lang, config('app.languages'))) {
//        $location = Location::get();
//        $lang = (!empty($location) && $location->countryCode == 'IR')?'fa':config('app.locale');
        $lang = config('app.locale');
    }

    \App::setLocale($lang);
}

function getSymbol($inp, $utf8 = true)
{
    $inp = explode(' ', $inp);
    foreach ($inp as $i)
        if (!in_array($i, trans('nologo.remove_words'))) {
            $str = ($utf8) ? fa2en(mb_substr($i, 0, 1, 'UTF8')) : substr($i, 0, 1);
            return ucwords($str);
            break;
        }
    return ucwords('n');
}

function getFirst($inp, $maxlen = 6, $newlen = 4)
{
    $inp = explode(' ', $inp);
    foreach ($inp as $i)
        if (!in_array($i, trans('nologo.remove_words'))) {
            return ucwords(strlen(utf8_decode($i)) > $maxlen ? mb_substr($i, 0, $newlen) : $i);
            break;
        }
    return '';
}

function getName($inp, $default = 'No Name', $maxlen = 20, $newlen = 10)
{
    if (!isset($inp)) $inp = $default;
    $inp = explode(' ', $inp);
    $check = true;
    $ret = [];
    foreach ($inp as $i) {
        if ($check && in_array($i, trans('nologo.remove_words'))) continue;
        $check = false;
        $ret[] = $i;
    }
    $ret = implode(' ', $ret);
    return ucwords(strlen(utf8_decode($ret)) > $maxlen ? mb_substr($ret, 0, $newlen) : $ret);
}

function colorHex2RGB($inp)
{
    $rgb = ['0', '0', '0'];
    if (substr($inp, 0, 1) == '#') $inp = substr($inp, 1);
    if (strlen($inp) == 6)
        for ($i = 0; $i < 3; $i++)
            $rgb[$i] = hexdec(substr($inp, 2 * $i, 2));

    return $rgb;

}


function getlangfolder($lang)
{
    return (in_array($lang, ['fa', 'ar'])) ? '_rtl' : '_ltr';
//    return ($lang == 'fa')?'_rtl':'_ltr'.$lang;
}

function array_to_stream($arr, $sep = ' - ')
{
    return implode($sep, array_values($arr));
}

function filter_by_key($inp_arr, $filter)
{
    $arr = [];
    foreach ($inp_arr as $key => $val) if (strpos($key, $filter) === 0) $arr[$key] = $val;
    return $arr;
}

function getAllTranslates($code, $unique = false)
{
    $ret = [];
    foreach (config('app.languages') as $lang) {
        $ret[$lang] = trans($code, [], $lang);
    }
    if ($unique) $ret = array_unique($ret);
    return $ret;
}

function trans_rev($inp = '', $nocase = true, $lang = null)
{
    if ($nocase) $inp = strtolower($inp);
    if (is_null($lang)) $lang = \App::getLocale();
    $folder = App::langPath() . '/' . $lang;
    $tmp_files = scandir($folder);
    $tr = [];
    foreach ($tmp_files as $file) {
        if (is_dir($file)) continue;
        $file = str_replace('.php', '', $file);
        try {
            $tmp = trans($file, [], $lang);
            if ($nocase) $tmp = array_map('strtolower', $tmp);
            $tr[$file] = array_flip($tmp);
        } catch (Exception $e) {
        }
    }

    $ret = [];
    foreach ($tr as $k => $t) {
        if (isset($t[$inp])) $ret[] = $k . '.' . $t[$inp];
    }
    return $ret;
}

function removeWhites($inp)
{
    return preg_replace('/\W+/', '', trim($inp));
}

function get_ip()
{
    return $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? Request::ip() ?? 'LOCAL';
//    return (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : (Request::exists('ip') ? Request::ip() : 'LOCAL');
}

function visitorIsDeveloper()
{
    return (\Schema::hasTable('brandings') && \App\Models\Branding::isSame('developer_ip', get_ip()));
}


function checkExUrl($url, $dd = false)
{
    $url = 'http://' . $url;
    $headers = @get_headers($url);
    dd($headers);
    if ($dd) dd($headers);
    if (!$headers || !in_array(substr($headers[0], 0, 3), ['200', '302', '301'])) return false;
}

function time2min($time, $type)
{
    $tmp = floatval($time);
    switch ($type) {
        case 'month':
            $tmp = $tmp * 30 * 24 * 60;
            break;
        case 'week':
            $tmp = $tmp * 7 * 24 * 60;
            break;
        case 'day':
            $tmp = $tmp * 24 * 60;
            break;
        case 'hour':
            $tmp = $tmp * 60;
            break;
    }

    return $tmp;
}

function substrBefore($inp, $bef)
{
    $arr = explode($bef, $inp, 2);
    return $arr[0];
}

function getDefView($base_view_folder, $pagename)
{
    $defs = ['.lists' => '/list', '' => '', '.forms' => '/form'];
    foreach ($defs as $def => $url)
        if (View::exists($base_view_folder . $def . '.' . $pagename)) return $url;
    return '';
}

function mapFolder($addr, $excludeDirs = [], $flashCache = false)
{
    if ($flashCache)
        \Cache::forget('FOLD_' . strtolower(trim($addr)));
    else
        if (\Cache::has('FOLD_' . strtolower(trim($addr)))) return json_decode(\Cache::get('FOLD_' . strtolower(trim($addr))), true);
    $ret = null;
    $ffs = scandir(base_path($addr));

    $ffs = array_diff($ffs, array_merge(['.', '..'], $excludeDirs));


    if (count($ffs) > 0)
        foreach ($ffs as $ff) {
            $fullAddress = base_path((empty($addr) ? '' : $addr . '/') . $ff);
            $ret[] = [is_dir($fullAddress) ? 'd' : 'f', $ff, base_path($addr)];
            if (is_dir($fullAddress)) {
                $tmp = mapFolder((empty($addr) ? '' : $addr . '/') . $ff);
                if (!is_null($tmp)) $ret = array_merge($ret, $tmp);
            }
        }

    \Cache::put('FOLD_' . strtolower(trim($addr)), json_encode($ret), 60);
    return $ret;

}

function isEnabledFunc($func)
{
    return is_callable($func) && false === stripos(ini_get("disable_functions"), $func);
}

function ModelsList()
{
    $full_path = app_path() . '/Models/';
    $files = scandir($full_path);
    foreach ($files as $i => &$file) {
        if (is_dir($file) || $file == 'Traits') unset($files[$i]);
        else $file = str_replace('.php', '', $file);
    }
    return $files;
}

function exportTableToCsv($model)
{
    $rows = $model::all();
    $path = 'dbbackups/csv/' . $model . date('_YMD_his', time());
    $file = fopen(base_path($path), 'w');
    foreach ($rows as $row) {
        fputcsv($file, $row->toArray());
    }
    fclose($file);
    return $file;
}

function RichSnippetPhone($inp, $code = '+98')
{
    return preg_replace('/^09/', $code . '9', $inp);
}

function image_persian_text($string)
{
    // Reverse the string
    $len = mb_strlen($string, 'utf-8');
    $result = '';
    for ($i = ($len - 1); $i >= 0; $i--) {
        $result .= mb_substr($string, $i, 1, 'utf-8');
    }
    // These chars work as space when a character comes after them, so the next character will not connect to them
    $spaces_after = array('', ' ', 'ا', 'آ', 'أ', 'إ', 'د', 'ذ', 'ر', 'ز', 'ژ', 'و', 'ؤ');
    // These chars work as space when a character comes before them, so the previous character will not connect to them
    $spaces_before = array('', ' ');
    // Persian chars with their different styles at different positions:
    // Alone, After a non-space char, Before a non-space char, between two non-space chars
    $chars = array();
    $chars[] = array('آ', 'ﺂ', 'آ', 'ﺂ');
    $chars[] = array('أ', 'ﺄ', 'ﺃ', 'ﺄ');
    $chars[] = array('إ', 'ﺈ', 'ﺇ', 'ﺈ');
    $chars[] = array('ا', 'ﺎ', 'ا', 'ﺎ');
    $chars[] = array('ب', 'ﺐ', 'ﺑ', 'ﺒ');
    $chars[] = array('پ', 'ﭗ', 'ﭘ', 'ﭙ');
    $chars[] = array('ت', 'ﺖ', 'ﺗ', 'ﺘ');
    $chars[] = array('ث', 'ﺚ', 'ﺛ', 'ﺜ');
    $chars[] = array('ج', 'ﺞ', 'ﺟ', 'ﺠ');
    $chars[] = array('چ', 'ﭻ', 'ﭼ', 'ﭽ');
    $chars[] = array('ح', 'ﺢ', 'ﺣ', 'ﺤ');
    $chars[] = array('خ', 'ﺦ', 'ﺧ', 'ﺨ');
    $chars[] = array('د', 'ﺪ', 'ﺩ', 'ﺪ');
    $chars[] = array('ذ', 'ﺬ', 'ﺫ', 'ﺬ');
    $chars[] = array('ر', 'ﺮ', 'ﺭ', 'ﺮ');
    $chars[] = array('ز', 'ﺰ', 'ﺯ', 'ﺰ');
    $chars[] = array('ژ', 'ﮋ', 'ﮊ', 'ﮋ');
    $chars[] = array('س', 'ﺲ', 'ﺳ', 'ﺴ');
    $chars[] = array('ش', 'ﺶ', 'ﺷ', 'ﺸ');
    $chars[] = array('ص', 'ﺺ', 'ﺻ', 'ﺼ');
    $chars[] = array('ض', 'ﺾ', 'ﺿ', 'ﻀ');
    $chars[] = array('ط', 'ﻂ', 'ﻃ', 'ﻄ');
    $chars[] = array('ظ', 'ﻆ', 'ﻇ', 'ﻈ');
    $chars[] = array('ع', 'ﻊ', 'ﻋ', 'ﻌ');
    $chars[] = array('غ', 'ﻎ', 'ﻏ', 'ﻐ');
    $chars[] = array('ف', 'ﻒ', 'ﻓ', 'ﻔ');
    $chars[] = array('ق', 'ﻖ', 'ﻗ', 'ﻘ');
    $chars[] = array('ک', 'ﻚ', 'ﻛ', 'ﻜ');
    $chars[] = array('ك', 'ﻚ', 'ﻛ', 'ﻜ');
    $chars[] = array('گ', 'ﮓ', 'ﮔ', 'ﮕ');
    $chars[] = array('ل', 'ﻞ', 'ﻟ', 'ﻠ');
    $chars[] = array('م', 'ﻢ', 'ﻣ', 'ﻤ');
    $chars[] = array('ن', 'ﻦ', 'ﻧ', 'ﻨ');
    $chars[] = array('و', 'ﻮ', 'ﻭ', 'ﻮ');
    $chars[] = array('ؤ', 'ﺆ', 'ﺅ', 'ﺆ');
    $chars[] = array('ی', 'ﯽ', 'ﯾ', 'ﯿ');
    $chars[] = array('ي', 'ﻲ', 'ﻳ', 'ﻴ');
    $chars[] = array('ئ', 'ﺊ', 'ﺋ', 'ﺌ');
    $chars[] = array('ه', 'ﻪ', 'ﮬ', 'ﮭ');
//	$chars[] = array('ه', 'ﻪ', 'ه', 'ﮭ');
    $chars[] = array('ۀ', 'ﮥ', 'ﮬ', 'ﮭ');
    $chars[] = array('ة', 'ﺔ', 'ﺗ', 'ﺘ');
    $chars[] = array(' ', ' ', ' ', ' ');

    $signs = [' ', ',', '،', '(', ')', '{', '}', '[', ']', '|', '\\', '/', ':', ';', '"', "'", '?', '؟', '«', '»'];

    // Start processing the reversed string
    $string = $result;
    $coding = 'utf-8';
    $len = mb_strlen($string, $coding);
    $result = '';
//	$pn_arr = [];
//	$result_arr = [];

    $digit_seri = '';
    $in_digits = false;

    for ($i = 0; $i < $len; $i++) {
        $previous_char = $i > 0 ? mb_substr($string, $i - 1, 1, $coding) : '';
        $current_char = mb_substr($string, $i, 1, $coding);
        $next_char = $i < ($len - 1) ? mb_substr($string, $i + 1, 1, $coding) : '';
        $seen = false;

//                $pn_arr[] = [$i,$previous_char,$current_char,$next_char,$seen];
        $res_tmp = '';
        foreach ($chars as $char) {
            if ($in_digits && in_array($current_char, $signs)) break;
            if (in_array($current_char, $char)) {
                if (!in_array($next_char, $spaces_after) && !in_array($previous_char, $spaces_before)) {
                    $res_tmp = $char[3];
//                            $result .= $char[3];
//                            $result_arr[] = $char[3];
                } elseif (!in_array($previous_char, $spaces_before)) {
                    $res_tmp = $char[2];
//                            $result .= $char[2];
//                            $result_arr[] = $char[2];
                } elseif (!in_array($next_char, $spaces_after)) {
                    $res_tmp = $char[1];
//                            $result .= $char[1];
//                            $result_arr[] = $char[1];
                } else {
                    $res_tmp = $char[0];
//                            $result .= $char[0];
//                            $result_arr[] = $char[0];
                }
                $seen = true;
            }
        }
        if (!$seen) {
            $in_digits = true;
            $digit_seri = $current_char . $digit_seri;
//                    $result .= $current_char;
        } elseif ($in_digits) {
            $in_digits = false;
            $result .= $digit_seri;
//                    $result_arr[] = $digit_seri;
            $digit_seri = '';
        }
        $result .= $res_tmp;
//                $result_arr[] = $res_tmp;

    }
    if (!empty($digit_seri)) {
        $result .= $digit_seri;
//            $result_arr[] = $digit_seri;
    }
//dd($result_arr,$pn_arr);
    return $result;
}

function mb_wordwrap($str, $width = 75, $break = "\n", $cut = false)
{
    $lines = explode($break, $str);
    foreach ($lines as &$line) {
        $line = rtrim($line);
        if (mb_strlen($line) <= $width)
            continue;
        $words = explode(' ', $line);
        $line = '';
        $actual = '';
        foreach ($words as $word) {
            if (mb_strlen($actual . $word) <= $width)
                $actual .= $word . ' ';
            else {
                if ($actual != '')
                    $line .= rtrim($actual) . $break;
                $actual = $word;
                if ($cut) {
                    while (mb_strlen($actual) > $width) {
                        $line .= mb_substr($actual, 0, $width) . $break;
                        $actual = mb_substr($actual, $width);
                    }
                }
                $actual .= ' ';
            }
        }
        $line .= trim($actual);
    }
    return implode($break, $lines);
}

function setting($key, $default = null)
{
    return \Illuminate\Support\Facades\Schema::hasTable('settings') ? \App\Models\Setting::get($key, $default) : null;
}

function str_replace_first($from, $to, $content)
{
    $from = '/' . preg_quote($from, '/') . '/';
    return preg_replace($from, $to, $content, 1);
}


function includeRouteFiles($dir, $recursive = true, $exclusion = [])
{
    $route_files = scandir($dir);
    foreach ($route_files as $route_file) {
        if (in_array($route_file, ['.', '..', 'web.php', 'api.php'])) continue;
        if (in_array($route_file, $exclusion)) continue;
        if (is_dir($dir . '/' . $route_file)) {
            if ($recursive) includeRouteFiles($dir . '/' . $route_file);
            continue;
        }
        require_once($dir . '/' . $route_file);
    }
}

function getHttpStatusCode($url)
{
    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);

    /* Get the HTML or whatever is linked in $url. */
    $response = curl_exec($handle);

    $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

    curl_close($handle);

    return $httpCode;
}


function mb_str_word_count($unicode_string)
{
    // First remove all the punctuation marks & digits
    $unicode_string = preg_replace('/[[:punct:][:digit:]]/', '', $unicode_string);
    // Now replace all the whitespaces (tabs, new lines, multiple spaces) by single space
    $unicode_string = preg_replace('/[[:space:]]/', ' ', $unicode_string);
    // The words are now separated by single spaces and can be splitted to an array
    // I have included \n\r\t here as well, but only space will also suffice
    $words_array = preg_split("/[\n\r\t ]+/", $unicode_string, 0, PREG_SPLIT_NO_EMPTY);
    // Now we can get the word count by counting array elments
    return count($words_array);
}
