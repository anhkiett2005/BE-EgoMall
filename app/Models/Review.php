<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'rating',
        'comment',
        'review_status'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function reviewImages()
    {
        return $this->hasMany(ReviewImage::class,'review_id','order_id');
    }
}
