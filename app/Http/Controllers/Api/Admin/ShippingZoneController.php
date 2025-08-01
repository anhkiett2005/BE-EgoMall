<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShippingZoneRequest;
use App\Http\Resources\Admin\ShippingZoneResource;
use App\Response\ApiResponse;
use App\Services\ShippingMethodService;

class ShippingZoneController extends Controller
{
    protected ShippingMethodService $shippingMethodService;

    public function __construct(ShippingMethodService $shippingMethodService)
    {
        $this->shippingMethodService = $shippingMethodService;
    }

    public function store(ShippingZoneRequest $request, int $shippingMethodId)
    {
        $zone = $this->shippingMethodService->addShippingZone($shippingMethodId, $request->validated());

        return ApiResponse::success('Thêm phí vận chuyển theo tỉnh thành công!', 200, (new ShippingZoneResource($zone))->toArray(request()));
    }

    public function update(ShippingZoneRequest $request, int $shippingMethodId, int $zoneId)
    {
        $zone = $this->shippingMethodService->updateShippingZone($zoneId, $request->validated());

        return ApiResponse::success(
            'Cập nhật phí vận chuyển thành công!',
            200,
            (new ShippingZoneResource($zone))->toArray(request())
        );
    }

    public function destroy(int $shippingMethodId, int $zoneId)
    {
        $this->shippingMethodService->deleteShippingZone($zoneId);

        return ApiResponse::success('Xóa phí vận chuyển theo tỉnh thành thành công!');
    }
}