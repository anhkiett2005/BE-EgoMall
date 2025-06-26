<?php

namespace App\Http\Controllers\Api\Front;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
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
            $coupons = Coupon::all();
        } catch(Exception $e) {
            throw new ApiException('Có lỗi xảy ra!!');
        }
    }
}
