<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PromotionProduct extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'promotion_product';

    protected $fillable = [
        'promotion_id',
        'product_id'
    ];

    protected $hidden = [
        'id'
    ];
}
