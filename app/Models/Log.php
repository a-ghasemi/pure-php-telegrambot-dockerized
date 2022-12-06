<?php

namespace App\Models;

use App\Support\Database\CacheQueryBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'title',
        'user_id',
        'ip',
        'type',
        'message',
        'curr_url',
        'prev_url',
        'mili',
    ];

    protected $appends = [
        'summary',
        'ip_info',
//        'ip_location',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    # -----------------------------------

    public function setMiliAttribute($value)
    {
        $microtime = microtime(true);
        $this->attributes['mili'] = ($microtime - floor($microtime)) * 1000;
    }

    #TODO: activate after adding location info
    /*
        public function getIpLocationAttribute()
        {
            return ((!empty($this->ip) && $this->ip !== 'LOCAL') ? Location::get($this->ip) : null);
        }*/

    public function setIpAttribute($value)
    {
        $this->attributes['ip'] = get_ip();
    }

    public function getIpInfoAttribute()
    {
        return (ip2long($this->ip) !== false) ?
                "<a href='https://ipinfo.io/{$this->ip}'>{$this->ip}</a>"
                : $this->ip;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setCurrUrlAttribute($value)
    {
        $this->attributes['curr_url'] = url()->current();
    }

    public function setPrevUrlAttribute($value)
    {
        $this->attributes['prev_url'] = url()->previous();
    }

    public function setUserIdAttribute($value)
    {
        $this->attributes['user_id'] = auth()->id() ?? null;
    }

    public function getSummaryAttribute()
    {
        $string = strip_tags($this->message);
        $needle = ' ';
        $nth = 50;

        $max = strlen($string);
        $n = 0;
        for ($i = 0; $i < $max; $i++) {
            if ($string[$i] == $needle) {
                $n++;
                if ($n >= $nth) break;
            }
        }
        $ret = (($n < $nth) ? $string : substr($string, 0, $i) . ' ...');

        return $ret;
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }

}
