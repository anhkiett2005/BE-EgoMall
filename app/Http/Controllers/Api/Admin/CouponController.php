<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCouponRequest;
use App\Http\Requests\UpdateCouponRequest;
use App\Http\Resources\Admin\CouponResource;
use App\Response\ApiResponse;
use App\Services\CouponService;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    protected $couponService;

    public function __construct(CouponService $couponService)
    {
        $this->couponService = $couponService;
    }

    public function index()
    {
        $coupons = $this->couponService->list();

        return ApiResponse::success(
            'Lấy danh sách mã giảm giá thành công!',
            200,
            CouponResource::collection($coupons)->resolve()
        );
    }

    public function store(StoreCouponRequest $request)
    {
        $coupon = $this->couponService->store($request->validated());

        return ApiResponse::success(
            'Tạo mã giảm giá thành công!',
            201,
            (new CouponResource($coupon))->resolve()
        );
    }

    public function show($id)
    {
        $coupon = $this->couponService->getById($id);

        return ApiResponse::success(
            'Lấy chi tiết mã giảm giá thành công!',
            200,
            (new CouponResource($coupon))->resolve()
        );
    }

    public function update(UpdateCouponRequest $request, $id)
    {
        $coupon = $this->couponService->update($id, $request->validated());

        return ApiResponse::success(
            'Cập nhật mã giảm giá thành công!',
            200,
            (new CouponResource($coupon))->resolve()
        );
    }

    public function destroy($id)
    {
        $this->couponService->delete($id);

        return ApiResponse::success('Xoá mã giảm giá thành công!');
    }

    public function restore($id)
    {
        $coupon = $this->couponService->restore($id);

        return ApiResponse::success(
            'Khôi phục mã giảm giá thành công!',
            200,
            (new CouponResource($coupon))->resolve()
        );
    }
}
