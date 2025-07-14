<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
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

    public function index(string $slug)
    {
        $reviews = $this->reviewService->listByProductSlug($slug);
        return ApiResponse::success('Lấy danh sách đánh giá thành công!', 200, ReviewResource::collection($reviews)->toArray(request()));
    }

    public function show($id)
    {
        $userId = auth('api')->id();

        $review = $this->reviewService->findById($id, $userId);

        return ApiResponse::success('Chi tiết đánh giá sản phẩm', 200, (new ReviewResource($review))->toArray(request()));
    }


    public function store(StoreReviewRequest $request)
    {
        $userId = auth('api')->id();

        $review = $this->reviewService->create($request->validated(), $userId);

        return ApiResponse::success('Đánh giá sản phẩm thành công!', 200, (new ReviewResource($review))->toArray(request()));
    }

    public function update(UpdateReviewRequest $request, int $id)
    {
        $userId = auth('api')->id();
        $review = $this->reviewService->update($id, $request->validated(), $userId);

        return ApiResponse::success('Cập nhật đánh giá thành công!', 200, (new ReviewResource($review))->toArray(request()));
    }
}