<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blog;
use App\Response\ApiResponse;
use App\Services\BlogService;
use Illuminate\Http\JsonResponse;

class BlogController extends Controller
{
    protected BlogService $blogService;

    public function __construct(BlogService $blogService)
    {
        $this->blogService = $blogService;
    }

    public function index(Request $request): JsonResponse
    {
        $blogs = $this->blogService->listPublished($request->all());
        return ApiResponse::success('Danh sách blog hiển thị', 200, $blogs);
    }

    public function showBySlug(string $slug): JsonResponse
    {
        $blog = $this->blogService->showBySlug($slug);
        return ApiResponse::success('Lấy chi tiết bài viết thành công', 200, $blog->toArray());
    }

    public function topViewed(): JsonResponse
    {
        $blogs = $this->blogService->topViewed();
        return ApiResponse::success('Top bài viết nổi bật', 200, $blogs);
    }
}
