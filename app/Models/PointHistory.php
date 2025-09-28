<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointHistory extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'point',
        'point_type',
        'consumption_points',
        'customer_point',
        'transaction_type',
    ];

    protected $casts = [
        'point' => 'float',
        'consumption_points' => 'float',
        'customer_point' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
