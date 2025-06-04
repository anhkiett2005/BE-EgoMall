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
    ];

    protected $hidden = [
        'id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function categories()
    {
        return $this->hasMany(Category::class);
    }
}
