<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\Front\ReviewResource;
use App\Response\ApiResponse;
use App\Services\ReviewService;

class ReviewController extends Controller
{
    protected $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    public function store(StoreReviewRequest $request)
    {
        $userId = auth('api')->id();

        $review = $this->reviewService->create($request->validated(), $userId);

        return ApiResponse::success('Đánh giá sản phẩm thành công!', 200, (new ReviewResource($review))->toArray(request()));
    }
}