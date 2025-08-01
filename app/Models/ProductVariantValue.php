<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductVariantValue extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'product_variant_values';

    protected $fillable = [
        'product_variant_id',
        'variant_value_id',
    ];

    // public function productVariant()
    // {
    //     return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    // }

    // public function variantValue()
    // {
    //     return $this->belongsTo(VariantValue::class);
    // }
}
