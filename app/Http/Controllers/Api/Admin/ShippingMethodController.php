<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShippingMethodRequest;
use App\Http\Resources\Admin\ShippingMethodDetailResource;
use App\Http\Resources\Admin\ShippingMethodResource;
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

    public function index()
    {
        $shippingMethods = $this->shippingMethodService->getAll();

        return ApiResponse::success(
            'Lấy danh sách phương thức vận chuyển thành công!',
            200,
            ShippingMethodResource::collection($shippingMethods)->toArray(request())
        );
    }


    public function show($id)
    {
        $shippingMethod = $this->shippingMethodService->getById($id);

        return ApiResponse::success(
            'Chi tiết phương thức vận chuyển!',
            200,
            (new ShippingMethodDetailResource($shippingMethod))->toArray(request())
        );
    }


    public function store(ShippingMethodRequest $request)
    {
        $shippingMethod = $this->shippingMethodService->create($request->validated());

        return ApiResponse::success(
            'Tạo phương thức vận chuyển thành công!',
            200,
            (new ShippingMethodResource($shippingMethod))->toArray(request())
        );
    }

    public function update(int $id, ShippingMethodRequest $request)
    {
        $method = $this->shippingMethodService->update($id, $request->validated());
        return ApiResponse::success(
            'Cập nhật phương thức vận chuyển thành công!',
            200,
            (new ShippingMethodResource($method))->toArray(request())
        );
    }

    public function destroy(int $id)
    {
        $this->shippingMethodService->delete($id);

        return ApiResponse::success('Xoá mềm phương thức vận chuyển thành công!');
    }
}