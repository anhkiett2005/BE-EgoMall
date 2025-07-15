<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ReviewAdminResource;
use App\Response\ApiResponse;
use App\Services\ReviewAdminService;
use Illuminate\Http\Request;

class ReviewAdminController extends Controller
{
    protected $reviewAdminService;

    public function __construct(ReviewAdminService $reviewAdminService)
    {
        $this->reviewAdminService = $reviewAdminService;
    }


    public function index(Request $request)
    {
        $reviews = $this->reviewAdminService->list($request->all());

        return ApiResponse::success('Danh sách đánh giá', 200, ReviewAdminResource::collection($reviews)->toArray($request));
    }

    public function toggleVisibility(int $reviewId)
    {
        $review = $this->reviewAdminService->toggleVisibility($reviewId);

        return ApiResponse::success('Cập nhật trạng thái hiển thị thành công!', 200, [
            'id' => $review->id,
            'is_visible' => $review->is_visible
        ]);
    }

    public function destroy(int $reviewId)
    {
        $this->reviewAdminService->delete($reviewId);

        return ApiResponse::success('Xóa đánh giá thành công!');
    }
}