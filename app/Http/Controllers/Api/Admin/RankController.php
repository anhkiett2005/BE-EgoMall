<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRankRequest;
use App\Http\Requests\UpdateRankRequest;
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

    public function update(UpdateRankRequest $request, string $id)
    {
        try {
            $isUpdated = $this->rankService->update($request, $id);

            if ($isUpdated) {
                return ApiResponse::success('Cập nhật rank thành công!!', data: (new RankResource($isUpdated))->resolve());
            }
        } catch(ApiException $e) {
            throw $e;
        }
    }

    public function destroy(string $id)
    {
        try {
            $isDeleted = $this->rankService->destroy($id);

            if ($isDeleted) {
                return ApiResponse::success('Xóa rank thành công!!');
            }
        } catch(ApiException $e) {
            throw $e;
        }
    }
}
