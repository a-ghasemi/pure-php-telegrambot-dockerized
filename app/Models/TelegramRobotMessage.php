<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class TelegramRobotMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        # My Side
        'title',
        'robot_id',
        'user_id',
        'user_telegram_id',
        'session_id',
        'returnof_id',
        'status',
        'mili',

        # Telegram Side
        'update_id',
        'reply_type',
        'reply_id',
        'reply_status',
        'reply_from_id',
        'reply_from_isbot',
        'reply_from_firstname',
        'reply_from_lastname',
        'reply_from_username',
        'reply_from_language_code',
        'reply_othcontent',
        'reply_raw',
    ];

    protected $casts = ['reply_othcontent' => 'array'];

    # -----------------------------------

    public function robot()
    {
        return $this->belongsTo(BotConnection::class, 'robot_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setMiliAttribute($value)
    {
        $microtime = microtime(true);
        $this->attributes['mili'] = ($microtime - floor($microtime)) * 1000;
    }

    public static function handleReceive(Request $request, $bot_id = null)
    {
        $arr = [
            //My Side
            'robot_id'                 => $bot_id,
            'mili'                     => null,
            'status'                   => ($bot_id ? 'received' : null),
            'user_telegram_id'         => $request->tlg_reply[$request->reply_type]['from']['id'],
            'returnof_id'              => null,
            //Telegram Side
            'update_id'                => $request->tlg_update_id,
            'reply_type'               => $request->reply_type,
            'reply_id'                 => $request->tlg_reply[$request->reply_type][$request->reply_type == 'message' ? 'message_id' : 'id'],
            'reply_status'             => null,
            'reply_from_id'            => $request->tlg_reply[$request->reply_type]['from']['id'],
            'reply_from_isbot'         => ($request->tlg_reply[$request->reply_type]['from']['is_bot'] !== '0'),
            'reply_from_firstname'     => $request->tlg_reply[$request->reply_type]['from']['first_name'],
            'reply_from_lastname'      => isset($request->tlg_reply[$request->reply_type]['from']['last_name']) ? $request->tlg_reply[$request->reply_type]['from']['last_name'] : null,
            'reply_from_username'      => isset($request->tlg_reply[$request->reply_type]['from']['username']) ? $request->tlg_reply[$request->reply_type]['from']['username'] : null,
            'reply_from_language_code' => isset($request->tlg_reply[$request->reply_type]['from']['language_code']) ? $request->tlg_reply[$request->reply_type]['from']['language_code'] : null,
            'reply_raw'                => json_encode($request->tlg_reply),
        ];

        $tmp = $request->tlg_reply[$request->reply_type];
        unset($tmp['id'], $tmp['message_id'], $tmp['from']);
        $arr['reply_othcontent'] = $tmp;

        $usr = User::isTelegramMember($arr['user_telegram_id']);
        $arr['user_id'] = $usr ? $usr->id : null;

        if (isset($request->tlg_reply['callback_query'])) {
            $msg_id = $request->tlg_reply['callback_query']['message']['message_id'];
            $tmp = static::where('reply_id', $msg_id)->first();
            $arr['returnof_id'] = $tmp ? $tmp->id : null;
        }

        return static::create($arr);
    }

    public static function updateStatus($id, $new_state)
    {
        return static::find($id)->update(['status', $new_state]);
    }

    public static function updateTelegramStatus($id, $new_state)
    {
        return static::find($id)->update(['reply_status', $new_state]);
    }

    public static function handleSend($title, $data, $bot_id = null)
    {
        $usr = User::isTelegramMember($data['params']['chat_id']);

        $arr = [
            //My Side
            'title'                    => $title,
            'robot_id'                 => $bot_id,
            'user_id'                  => (!empty($usr)) ? $usr->id : null,
            'user_telegram_id'         => $data['params']['chat_id'],
            'mili'                     => null,
            'status'                   => 'pending',
            'returnof_id'              => null,
            //Telegram Side
            'update_id'                => null,
            'reply_type'               => $data['method'],
            'reply_id'                 => null,
            'reply_status'             => null,
            'reply_from_language_code' => null,
            'reply_othcontent'         => $data['params'],
        ];

        return static::create($arr);
    }


    public static function dataTableQuery(string $status = null)
    {
        return (new static)->newQuery();

//        $query = new static;
//        if (!is_null($status)) $query = $query->where($query->getTable().'.status', $status);
//
//        $query = $query->leftJoin('agency_groups', 'agencies.group_id', '=', 'agency_groups.id')
//            ->select(['agencies.*', 'agency_groups.title as agency_group']);
//        return $query;
    }
}
