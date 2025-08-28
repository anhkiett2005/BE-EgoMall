<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'coupons';

    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'min_order_value',
        'max_discount',
        'usage_limit',
        'discount_limit',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_order_value' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'status' => 'boolean',
    ];

    protected $appends = ['is_voucher_valiable'];

    public function getIsVoucherValiableAttribute()
    {
        $user = auth('api')->user();

        // Đếm số lượt user đã dùng voucher này
        $usedCount = $this->usages()
            ->where('user_id', $user->id)
            ->count();

        // Nếu có giới hạn discount_limit
        if (!is_null($this->discount_limit)) {
            return $usedCount < $this->discount_limit;
        }

        // Nếu không set discount_limit thì dùng thoải mái
        return true;
    }

    // Danh sách lượt dùng
    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }


    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
