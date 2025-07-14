<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_detail_id',
        'user_id',
        'rating',
        'comment',
        'is_anonymous',
        'is_updated',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class);
    }

    public function images()
    {
        return $this->hasMany(ReviewImage::class);
    }

    public function replies()
    {
        return $this->hasOne(ReviewReply::class);
    }
}