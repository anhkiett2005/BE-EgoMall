<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'description',
        'is_active',
        'is_featured'
    ];

    protected $hidden = [
        'id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', '!=', 0);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', '!=', 0);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
