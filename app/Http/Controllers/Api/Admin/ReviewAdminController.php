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

    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected'
        ]);

        $review = $this->reviewAdminService->updateStatus($id, $request->input('status'));

        return ApiResponse::success('Cập nhật trạng thái đánh giá thành công!', 200, (new ReviewAdminResource($review))->toArray(request()));
    }

    public function destroy(int $reviewId)
    {
        $this->reviewAdminService->delete($reviewId);

        return ApiResponse::success('Xóa đánh giá thành công!');
    }
}
