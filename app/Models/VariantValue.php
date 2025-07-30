<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class VariantValue extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'variant_values';

    protected $fillable = [
        'option_id',
        'value',
    ];

    public function option()
    {
        return $this->belongsTo(VariantOption::class, 'option_id');
    }

    // public function productVariantValues()
    // {
    //     return $this->hasMany(ProductVariantValue::class);
    // }
}
