<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderDetail extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'product_variant_id',
        'quantity',
        'price',
        'sale_price',
        'is_gift',
    ];

    protected $casts = [
        'is_gift' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id', 'id');
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function product()
    {
        return $this->productVariant?->product;
    }
}