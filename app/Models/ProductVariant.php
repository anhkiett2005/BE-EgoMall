<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductVariant extends Model
{
    use HasFactory;

    protected $table = 'product_variants';
    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'slug',
        'price',
        'sale_price',
        'quantity',
        'stock_status'
    ];

    protected $hidden = [
        'id'
    ];

    protected $casts = [
        'price' => 'float',
        'sale_price' => 'float'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function values()
    {
        return $this->hasMany(ProductVariantValue::class, 'product_variant_id');
    }

    public function giftPromotions()
    {
        return $this->hasMany(Promotion::class, 'gift_product_variant_id');
    }

     public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
}
