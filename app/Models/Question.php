<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;
    protected $fillable = [
        'question_category_id',
        'telegram_user_id',
        'title',
        'type',
        'content',
        'order',
        'status',
    ];

    public function category(){
        return $this->belongsTo(QuestionCategory::class);
    }

    public function telegramId(){
        return $this->belongsTo(TelegramId::class,'telegram_user_id');
    }

}
