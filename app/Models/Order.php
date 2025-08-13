<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'user_id',
        'total_price',
        'total_discount',
        'discount_details',
        'status',
        'note',
        'shipping_name',
        'shipping_phone',
        'shipping_email',
        'shipping_address',
        'shipping_fee',
        'shipping_method_snapshot',
        'payment_method',
        'payment_created_at',
        'payment_status',
        'payment_date',
        'transaction_id',
        'coupon_id',
        'delivered_at',
        'cancel_reason',
    ];

    protected $hidden = [
        'id'
    ];

    protected $casts = [
        'discount_details' => 'json',
        'delivered_at' => 'datetime',
        'payment_created_at'  => 'datetime',
        'payment_date'        => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
