<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Http\Resources\Front\RankResource;
use App\Response\ApiResponse;
use App\Services\RankService;

class RankController extends Controller
{
    protected $rankService;

    public function __construct(RankService $rankService)
    {
        $this->rankService = $rankService;
    }

    public function index()
    {
        $listRanks = $this->rankService->modifyIndex();

        return ApiResponse::success(
        'Lấy danh sách ranks thành công!!',
         data: RankResource::collection($listRanks)->toArray(request())
        );
    }
}
