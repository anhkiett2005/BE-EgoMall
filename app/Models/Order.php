<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_price',
        'status',
        'note',
        'shipping_address',
        'shipping_phone',
        'payment_method',
        'payment_status',
        'payment_date',
        'transaction_id',
    ];

    protected $hidden = [
        'id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }
}
