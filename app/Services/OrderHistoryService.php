<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class OrderHistoryService
{
    public function listByUser(?string $status = null)
    {
        $userId = Auth::guard('api')->id();

        $query = Order::with([
            'details.productVariant.product',
            'details.productVariant.values',
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

    public function getByUniqueId(string $uniqueId)
    {
        $userId = auth('api')->id();

        $order = Order::with([
            'details.productVariant.product',
            'details.productVariant.values',
            'coupon',
        ])->where('unique_id', $uniqueId)
            ->where('user_id', $userId)
            ->first();

        if (!$order) {
            throw new ApiException('Không tìm thấy đơn hàng!', 404);
        }

        return $order;
    }
}
