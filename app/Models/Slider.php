<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\Fluent\Concerns\Has;

class Slider extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'position',
        'status',
    ];

    public function images()
    {
        return $this->hasMany(SliderImage::class);
    }
}
