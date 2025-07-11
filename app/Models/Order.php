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
        'payment_method',
        'payment_status',
        'payment_date',
        'transaction_id',
    ];

    protected $hidden = [
        'id'
    ];

    protected $casts = [
        'discount_details' => 'json'
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

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
