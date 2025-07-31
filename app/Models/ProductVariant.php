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
        'sku',
        'price',
        'sale_price',
        'quantity',
        'is_active',
    ];

    protected $hidden = [
        'id'
    ];

    protected $casts = [
        'price' => 'float',
        'sale_price' => 'float',
        'is_active' => 'boolean'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function values()
    {
        return $this->belongsToMany(VariantValue::class, 'product_variant_values', 'product_variant_id', 'variant_value_id')
            ->with('option');
    }

    public function getVariantNameAttribute()
    {
        return $this->values->map(function ($v) {
            return ($v->option->name ?? '') . ': ' . $v->value;
        })->implode(' | ');
    }

    public function giftPromotions()
    {
        return $this->hasMany(Promotion::class, 'gift_product_variant_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'product_variant_id', 'id');
    }
}