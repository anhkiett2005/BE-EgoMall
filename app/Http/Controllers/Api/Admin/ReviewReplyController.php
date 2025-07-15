<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReplyRequest;
use App\Http\Requests\UpdateReplyRequest;
use App\Http\Resources\Admin\ReviewReplyResource;
use App\Response\ApiResponse;
use App\Services\ReviewAdminService;

class ReviewReplyController extends Controller
{
    protected $reviewReplyService;

    public function __construct(ReviewAdminService $reviewReplyService)
    {
        $this->reviewReplyService = $reviewReplyService;
    }

    /**
     * Tạo phản hồi đánh giá từ nhân viên
     */
    public function store(StoreReplyRequest $request)
    {
        $userId = auth('api')->id();

        $reply = $this->reviewReplyService->create($request->validated(), $userId);

        return ApiResponse::success('Phản hồi thành công!', 200, (new ReviewReplyResource($reply))->toArray(request()));
    }

    public function update(UpdateReplyRequest $request, int $reviewId)
    {
        $userId = auth('api')->id();

        $reply = $this->reviewReplyService->update($reviewId, $request->validated(), $userId);

        return ApiResponse::success('Cập nhật phản hồi thành công!', 200, (new ReviewReplyResource($reply))->toArray(request()));
    }
}