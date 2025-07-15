<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_detail_id',
        'user_id',
        'rating',
        'comment',
        'is_anonymous',
        'is_visible',
    ];

    protected $appends = ['is_updated'];

    public function getIsUpdatedAttribute(): bool
    {
        return $this->updated_at != $this->created_at;
    }

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

    public function reply()
    {
        return $this->hasOne(ReviewReply::class);
    }
}