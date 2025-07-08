<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Http\Resources\Front\ProvinceResource;
use App\Http\Resources\Front\DistrictResource;
use App\Http\Resources\Front\WardResource;
use App\Response\ApiResponse;
use App\Services\LocationService;

class LocationController extends Controller
{
    protected LocationService $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    public function getProvinces()
    {
        $provinces = $this->locationService->getProvinces();
        return ApiResponse::success(
            'Lấy danh sách tỉnh/thành thành công!',
            200,
            ProvinceResource::collection($provinces)->toArray(request())
        );
    }

    public function getDistricts(string $provinceCode)
    {
        $districts = $this->locationService->getDistricts($provinceCode);
        return ApiResponse::success(
            'Lấy danh sách quận/huyện thành công!',
            200,
            DistrictResource::collection($districts)->toArray(request())
        );
    }

    public function getWards(string $districtCode)
    {
        $wards = $this->locationService->getWards($districtCode);
        return ApiResponse::success(
            'Lấy danh sách phường/xã thành công!',
            200,
            WardResource::collection($wards)->toArray(request())
        );
    }
}
