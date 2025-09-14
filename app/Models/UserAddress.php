<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserAddress extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'province_code',
        'district_code',
        'ward_code',
        'address_detail',
        'address_name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'note',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    // ==== QUAN HỆ ====
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_code', 'code');
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class, 'ward_code', 'code');
    }

    // ==== ACCESSOR: full_address ====
    public function getFullAddressAttribute(): string
    {
        return "{$this->address_detail}, {$this->ward->full_name}, {$this->district->full_name}, {$this->province->full_name}";
    }
}
