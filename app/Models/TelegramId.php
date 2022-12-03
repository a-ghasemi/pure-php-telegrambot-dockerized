<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramId extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'telegram_id',
        'username',
        'firstname',
        'lastname',
        'language',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
