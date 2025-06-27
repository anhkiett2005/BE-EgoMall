<?php

namespace App\Http\Controllers\Api\Front;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Response\ApiResponse;
use Exception;

class CouponController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Lấy coupon trả về cho fe
            $coupons = Coupon::with('orders')
                             ->where(function ($q) {
                                    $q->whereNull('usage_limit')
                                        ->orWhereRaw('(
                                                SELECT COUNT(*) FROM `orders` WHERE `orders`.`coupon_id` = `coupons`.`id`
                                        ) < `usage_limit`');
                                })
                                ->whereDate('start_date', '<=', now())
                                ->whereDate('end_date', '>=', now())
                                ->where('status', '!=', 0)
                                ->get();

            // Xử lý data trả về
            $listCoupon = collect();

            foreach($coupons as $coupon) {
               $data = [
                        'code' => $coupon->code,
                        'description' => $coupon->description,
                        'discount_type' => $coupon->discount_type,
                        'discount_value' => $coupon->discount_value,
                        'conditions' => $coupon->min_order_value,
                        'end_date' => $coupon->end_date->format('d-m-Y H:i'),
                    ];

                    // Nếu loại là percent thì thêm max_discount
                    if ($coupon->discount_type === 'percent') {
                        $data['max_discount'] = $coupon->max_discount;
                    }

                    $listCoupon->push($data);
            }
            return ApiResponse::success('Lấy danh sách coupon thành công!!', data: $listCoupon);
        } catch(Exception $e) {
            throw new ApiException('Có lỗi xảy ra!!');
        }
    }
}
