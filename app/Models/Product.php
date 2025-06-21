<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'category_id',
        'is_variable',
        'is_active',
        'brand_id',
        'type_skin',
        'description',
        'image'
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

    public function giftPromotions()
    {
        return $this->hasMany(Promotion::class, 'gift_product_id');
    }
}
