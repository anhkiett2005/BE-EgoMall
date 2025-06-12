<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Promotion extends Model
{
    use HasFactory;

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
        return $this->hasManyThrough(Product::class, PromotionProduct::class, 'promotion_id', 'id', 'id', 'product_id');
    }

    public function productVariants()
    {
        return $this->hasManyThrough(ProductVariant::class, PromotionProduct::class, 'promotion_id', 'id', 'id', 'product_variant_id');
    }
}
