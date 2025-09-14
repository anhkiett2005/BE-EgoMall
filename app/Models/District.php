<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $table = 'districts';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'name_en',
        'full_name',
        'full_name_en',
        'code_name',
        'province_code',
        'administrative_unit_id',
    ];

    // Quan hệ n-1 với Province
    public function province()
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    // Quan hệ 1-n với Ward
    public function wards()
    {
        return $this->hasMany(Ward::class, 'district_code', 'code');
    }
}
