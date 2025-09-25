<?php

namespace App\Models;

use App\Classes\Common;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

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

    protected $appends = ['variant_name', 'final_price_discount', 'option_labels'];

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
        $values = $this->values->map(function ($v) {
            return ($v->option->name ?? '') . ': ' . $v->value;
        })->implode(' | ');

        return "Sản phẩm {$this->product->name} ({$values})";
    }

    public function getOptionLabelsAttribute()
    {
        return $this->values
            ->map(function ($value) {
                return ($value->option->name ?? 'Thuộc tính') . ": " . $value->value;
            })
            ->implode(' | ');
    }

    public function getFinalPriceDiscountAttribute()
    {
            // Lấy promotion đang active
            $promotion = Cache::get('active_promotions') ?? Common::getActivePromotion();

            // Tính toán giảm giá
            return Common::checkPromotion($this, $promotion);
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

    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'promotion_product', 'product_variant_id', 'promotion_id');
    }
}
