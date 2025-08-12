<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Http\Resources\Front\PublicSettingResource;
use App\Response\ApiResponse;
use App\Services\SystemSettingService;

class PublicSettingController extends Controller
{
    public function __construct(private SystemSettingService $service) {}

    public function show()
    {
        $data = $this->service->publicSettings();

        return ApiResponse::success(
            'Lấy public settings thành công!',
            200,
            (new PublicSettingResource($data))->toArray(request())
        );
    }
}