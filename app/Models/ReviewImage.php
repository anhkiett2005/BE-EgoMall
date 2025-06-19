<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReviewImage extends Model
{
    use HasFactory;

    protected $table = 'review_images';

    public $timestamps = false;
    protected $fillable = [
        'review_id',
        'image_url'
    ];

    public function review()
    {
        return $this->belongsTo(Review::class,'review_id','order_id');
    }
}
