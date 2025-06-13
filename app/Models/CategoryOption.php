<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategoryOption extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'category_options';

    protected $fillable = [
        'variant_option_id',
        'category_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variantOption()
    {
        return $this->belongsTo(VariantOption::class);
    }
}
