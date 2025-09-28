<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRankRequest;
use App\Http\Resources\Admin\RankResource;
use App\Response\ApiResponse;
use App\Services\RankService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RankController extends Controller
{
    protected $rankService;
    public function __construct(RankService $rankService)
    {
        $this->rankService = $rankService;
    }

    public function index()
    {
        $ranks = $this->rankService->adminIndex();

        return ApiResponse::success(
            'Lấy danh sách ranks thành công!!',
            data: RankResource::collection($ranks)->toArray(request())
        );
    }

    public function store(StoreRankRequest $request)
    {
        try {
            $rank = $this->rankService->store($request);

            if ($rank) {
                return ApiResponse::success('Tạo rank thành công!!', Response::HTTP_CREATED, (new RankResource($rank))->resolve());
            }
        } catch(ApiException $e) {
            throw $e;
        }
    }
}
