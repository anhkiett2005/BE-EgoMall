<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Collection;

class ShippingMethodService
{
    // User
    public function listAvailableByProvince(string $provinceCode): Collection
    {
        $methods = ShippingMethod::with(['zones' => function ($query) use ($provinceCode) {
            $query->where('province_code', $provinceCode)
                ->where('is_available', true);
        }])->where('is_active', true)->get();

        $filtered = $methods->filter(function ($method) {
            return $method->zones->isNotEmpty();
        });

        if ($filtered->isEmpty()) {
            throw new ApiException('Không có phương thức vận chuyển khả dụng tại khu vực này.', Response::HTTP_NOT_FOUND);
        }

        return $filtered;
    }


    //Admin
    //Shipping_Methods
    public function getAll(): Collection
    {
        return ShippingMethod::latest()->get();
    }

    public function getById(int $id): ShippingMethod
    {
        $method = ShippingMethod::with('zones')->find($id);

        if (!$method) {
            throw new ApiException('Không tìm thấy phương thức vận chuyển!', Response::HTTP_NOT_FOUND);
        }

        return $method;
    }

    public function create(array $data): ShippingMethod
    {
        // Nếu chọn là mặc định, thì reset tất cả phương thức khác về false
        if (!empty($data['is_default']) && $data['is_default']) {
            ShippingMethod::query()->update(['is_default' => false]);
        }

        return ShippingMethod::create([
            'name'           => $data['name'],
            'description'    => $data['description'] ?? null,
            'estimated_time' => $data['estimated_time'] ?? null,
            'is_active'      => $data['is_active'] ?? true,
            'is_default'     => $data['is_default'] ?? false,
        ]);
    }

    public function update(int $id, array $data): ShippingMethod
    {
        $method = $this->getById($id);

        if (!empty($data['is_default']) && $data['is_default']) {
            ShippingMethod::query()->update(['is_default' => false]);
        }

        $method->update([
            'name'           => $data['name'],
            'description'    => $data['description'] ?? null,
            'estimated_time' => $data['estimated_time'] ?? null,
            'is_active'      => $data['is_active'] ?? true,
            'is_default'     => $data['is_default'] ?? false,
        ]);

        return $method;
    }

    public function delete(int $id): void
    {
        $method = ShippingMethod::find($id);

        if (!$method) {
            throw new ApiException('Không tìm thấy phương thức vận chuyển.', Response::HTTP_NOT_FOUND);
        }

        $method->delete();
    }






    // Shipping_Zones

    public function addShippingZone(int $methodId, array $data): ShippingZone
    {
        $method = ShippingMethod::find($methodId);

        if (!$method) {
            throw new ApiException('Không tìm thấy phương thức vận chuyển.', Response::HTTP_NOT_FOUND);
        }

        // Nếu đã tồn tại zone cho province_code này thì ném lỗi
        $exists = ShippingZone::where('shipping_method_id', $methodId)
            ->where('province_code', $data['province_code'])
            ->exists();

        if ($exists) {
            throw new ApiException('Đã tồn tại phí vận chuyển cho tỉnh này.', Response::HTTP_CONFLICT);
        }

        return $method->zones()->create([
            'province_code' => $data['province_code'],
            'fee'           => $data['fee'],
            'is_available'  => $data['is_available'] ?? true,
        ]);
    }

    public function updateShippingZone(int $zoneId, array $data): ShippingZone
    {
        $zone = ShippingZone::find($zoneId);

        if (!$zone) {
            throw new ApiException('Không tìm thấy phí vận chuyển!', Response::HTTP_NOT_FOUND);
        }

        $zone->update([
            'fee'          => $data['fee'],
            'is_available' => $data['is_available'] ?? true,
        ]);

        return $zone;
    }

    public function deleteShippingZone(int $zoneId): void
    {
        $zone = ShippingZone::find($zoneId);

        if (!$zone) {
            throw new ApiException('Không tìm thấy phí vận chuyển!', Response::HTTP_NOT_FOUND);
        }

        $zone->delete();
    }
}