<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    public function addShippingZones(int $methodId, array $data): \Illuminate\Support\Collection
    {
        $method = ShippingMethod::find($methodId);
        if (!$method) {
            throw new ApiException('Không tìm thấy phương thức vận chuyển.', Response::HTTP_NOT_FOUND);
        }

        $codes = array_values(array_unique($data['province_codes'] ?? []));
        if (empty($codes)) {
            throw new ApiException('Danh sách tỉnh/thành rỗng.', Response::HTTP_BAD_REQUEST);
        }

        // Tìm các mã đã tồn tại để báo lỗi sớm
        $existing = ShippingZone::where('shipping_method_id', $methodId)
            ->whereIn('province_code', $codes)
            ->pluck('province_code')
            ->all();

        if (!empty($existing)) {
            throw new ApiException(
                'Đã tồn tại phí vận chuyển cho các tỉnh: ' . implode(', ', $existing),
                Response::HTTP_CONFLICT
            );
        }

        try {
            return DB::transaction(function () use ($method, $codes, $data) {
                $payload = array_map(function ($code) use ($data) {
                    return [
                        'province_code' => $code,
                        'fee'           => $data['fee'],
                        'is_available'  => $data['is_available'] ?? true,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                }, $codes);

                // Tạo hàng loạt
                ShippingZone::insert(array_map(function ($row) use ($method) {
                    return ['shipping_method_id' => $method->id] + $row;
                }, $payload));

                return ShippingZone::where('shipping_method_id', $method->id)
                    ->whereIn('province_code', $codes)
                    ->get();
            });
        } catch (\Exception $e) {
            logger('Log bug addShippingZones', [
                'error_message' => $e->getMessage(),
                'error_file'    => $e->getFile(),
                'error_line'    => $e->getLine(),
                'stack_trace'   => $e->getTraceAsString()
            ]);
            throw new ApiException('Không thể thêm phí vận chuyển theo tỉnh!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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