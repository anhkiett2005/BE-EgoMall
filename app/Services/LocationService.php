<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Location\Province;
use App\Models\Location\District;
use Illuminate\Database\Eloquent\Collection;

class LocationService
{
    public function getProvinces(): Collection
    {
        return Province::orderBy('full_name')->get();
    }

    public function getDistricts(string $provinceCode): Collection
    {
        $province = Province::with('districts')->find($provinceCode);
        if (!$province) {
            throw new ApiException('Không tìm thấy tỉnh/thành!', 404);
        }

        return $province->districts;
    }

    public function getWards(string $districtCode): Collection
    {
        $district = District::with('wards')->find($districtCode);
        if (!$district) {
            throw new ApiException('Không tìm thấy quận/huyện!', 404);
        }

        return $district->wards;
    }
}
