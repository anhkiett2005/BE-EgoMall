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
                                    $q->whereRaw('(
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
                        'id' => $coupon->id,
                        'code' => $coupon->code,
                        'description' => $coupon->description,
                        'discount_type' => $coupon->discount_type,
                        'discount_value' => (int) $coupon->discount_value,
                        'conditions' => (int) $coupon->min_order_value,
                        'end_date' => $coupon->end_date->format('d-m-Y H:i'),
                    ];

                    // Nếu loại là percent thì thêm max_discount
                    if ($coupon->discount_type === 'percent') {
                        $data['max_discount'] = (int) $coupon->max_discount;
                    }

                    $listCoupon->push($data);
            }
            return ApiResponse::success('Lấy danh sách voucher thành công!!', data: $listCoupon);
        } catch(Exception $e) {
            logger('Log bug',[
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!');
        }
    }
}
