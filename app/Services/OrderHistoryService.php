<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class OrderHistoryService
{
    public function listByUser(?string $status = null)
    {
        $userId = Auth::guard('api')->id();

        $query = Order::with([
            'details.productVariant.product',
            'details.productVariant.values.variantValue.option',
            'review'
        ])
            ->where('user_id', $userId)
            ->latest();

        if ($status && $status !== 'all') {
            if ($status === 'needReview') {
                $query->where('status', 'delivered')->whereDoesntHave('review');
            } else {
                $query->where('status', $status);
            }
        }

        return $query->get();
    }
}