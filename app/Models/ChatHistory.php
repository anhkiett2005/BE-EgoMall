<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatHistory extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'question',
        'answer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
