<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VariantOption extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'variant_options';

    protected $fillable = [
        'name'
    ];

    public function variantValues()
    {
        return $this->hasMany(VariantValue::class, 'option_id');
    }

    public function categoryOptions()
    {
        return $this->hasMany(CategoryOption::class, 'variant_option_id');
    }
}
