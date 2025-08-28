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
        'is_active' => 'boolean'
    ];

    // protected $appends = ['is_featured'];

    // public function getIsFeaturedAttribute()
    // {
    //     $totalSold = OrderDetail::from('order_details as od')
    //                             ->join('product_variants as pv', 'od.product_variant_id', '=', 'pv.id')
    //                             ->join('orders as o', 'o.id', '=', 'od.order_id')
    //                             ->where('pv.product_id', $this->id)
    //                             ->where('o.status', 'delivered')
    //                             ->where('od.is_gift', 0)
    //                             ->sum('od.quantity');

    //     return $totalSold >= 10;
    // }

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

    public function blogs()
    {
        return $this->belongsToMany(Blog::class, 'blog_product');
    }

    public function wishlistedByUsers()
    {
        return $this->belongsToMany(User::class, 'wishlists')->withTimestamps();
    }

    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'promotion_product', 'product_id', 'promotion_id');
    }
}
