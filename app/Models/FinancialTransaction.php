<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    protected $table = 'financial_transactions';

    protected $fillable = [
        'order_id',
        'amount',
        'vnpay_data',
        'momo_data',
        'sepay_data',
    ];

    protected $casts = [
        'vnpay_data' => 'array',
        'momo_data' => 'array',
        'sepay_data' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
