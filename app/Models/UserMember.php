<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserMember extends Model
{
    protected $fillable = [
        'user_id',
        'rank_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rank()
    {
        return $this->belongsTo(Rank::class);
    }
}
