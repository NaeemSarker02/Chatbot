<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatLog extends Model
{
    protected $fillable = [
        'user_message',
        'ai_raw_response',
        'parsed_action',
        'status',
        'error_message',
    ];
}
