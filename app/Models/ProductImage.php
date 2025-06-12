<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    /** @use HasFactory<\Database\Factories\ProductImageFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'product_images';

    protected $fillable = [
        'product_variant_id',
        'image_url'
    ];

    protected $hidden = [
        'id'
    ];

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
