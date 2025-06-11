<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\Fluent\Concerns\Has;

class Sliders extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'position',
        'status',
    ];
}
