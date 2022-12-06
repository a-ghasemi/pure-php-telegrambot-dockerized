<?php

namespace App\Models;

use App\Support\Database\CacheQueryBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Illuminate\Support\Facades\Log;

class BotConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'username',
        'robot_token',
        'parameters',
        'webhook_token',
        'active',
    ];

    protected $appends = ['params'];

    # -----------------------------------

}
