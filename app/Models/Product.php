<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category_id',
        'sku',
        'price',
        'sale_price',
        'quantity',
        'stock_status',
        'is_variable',
        'is_active',
        'brand_id',
        'type_skin',
        'description',
    ];

    protected $hidden = [
        'id'
    ];

    protected $casts = [
        'is_variable' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

     public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function giftPromotions()
    {
        return $this->hasMany(Promotion::class, 'gift_product_id');
    }
}
