<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Http\Resources\Front\ShippingMethodResource;
use App\Response\ApiResponse;
use App\Services\ShippingMethodService;
use Illuminate\Http\Request;

class ShippingMethodController extends Controller
{
    protected ShippingMethodService $shippingMethodService;

    public function __construct(ShippingMethodService $shippingMethodService)
    {
        $this->shippingMethodService = $shippingMethodService;
    }

    public function index(Request $request)
    {
        $provinceCode = $request->query('province_code');

        if (!$provinceCode) {
            return ApiResponse::error('Thiếu mã tỉnh/thành (province_code)', 422);
        }

        $methods = $this->shippingMethodService->listAvailableByProvince($provinceCode);

        return ApiResponse::success(
            'Danh sách phương thức vận chuyển khả dụng', 200, ShippingMethodResource::collection(collect($methods))->toArray($request)
        );
    }

       public function list()
    {
        $shippingMethods = $this->shippingMethodService->getAll();

        return ApiResponse::success(
            'Lấy danh sách phương thức vận chuyển thành công!',
            200,
            ShippingMethodResource::collection($shippingMethods)->toArray(request())
        );
    }
}
