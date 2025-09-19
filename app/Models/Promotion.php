<?php

namespace App\Models;

use App\Enums\PromotionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'promotions';
    protected $fillable = [
        'name',
        'description',
        'promotion_type',
        'discount_type',
        'discount_value',
        'start_date',
        'end_date',
        'status',
        'buy_quantity',
        'get_quantity',
        'gift_product_id',
        'gift_product_variant_id'
    ];

    protected $casts = [
        'status' => PromotionStatus::class,
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function giftProduct()
    {
        return $this->belongsTo(Product::class, 'gift_product_id');
    }

    public function giftProductVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'gift_product_variant_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'promotion_product', 'promotion_id', 'product_id');
    }

    public function productVariants()
    {
        return $this->belongsToMany(ProductVariant::class, 'promotion_product', 'promotion_id', 'product_variant_id');
    }
}
