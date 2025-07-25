<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'estimated_time',
        'is_active',
        'is_default',
    ];

    public function zones()
    {
        return $this->hasMany(ShippingZone::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
