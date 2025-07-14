<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReplyRequest;
use App\Http\Resources\Admin\ReviewReplyResource;
use App\Response\ApiResponse;
use App\Services\ReviewReplyService;

class ReviewReplyController extends Controller
{
    protected $reviewReplyService;

    public function __construct(ReviewReplyService $reviewReplyService)
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
}
