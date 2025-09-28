<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Rank extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image',
        'amount_to_point',
        'min_spent_amount',
        'converted_amount',
        'discount',
        'maximum_discount_order',
        'type_time_receive',
        'time_receive_point',
        'minimum_point',
        'maintenance_point',
        'point_limit_transaction',
        'status_payment_point',
    ];

    protected $casts = [
        'amount_to_point' => 'integer',
        'min_spent_amount' => 'integer',
        'converted_amount' => 'integer',
        'discount' => 'integer',
        'maximum_discount_order' => 'integer',
        'minimum_point' => 'integer',
        'maintenance_point' => 'integer',
        'point_limit_transaction' => 'integer',
        'status_payment_point' => 'boolean'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_members');
    }
}
