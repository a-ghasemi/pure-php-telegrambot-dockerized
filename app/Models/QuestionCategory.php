<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionCategory extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'order',
        'status',
    ];

    public function questions(){
        return $this->hasMany(Question::class);
    }

}
