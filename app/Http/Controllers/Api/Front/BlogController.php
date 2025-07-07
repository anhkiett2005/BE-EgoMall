<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Http\Resources\Front\BlogResource;
use Illuminate\Http\Request;
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
        return ApiResponse::success('Danh sách blog hiển thị', 200, BlogResource::collection($blogs)->toArray($request));
    }

    public function showBySlug(string $slug): JsonResponse
    {
        $blog = $this->blogService->showBySlug($slug);

        // Lấy 3 blog liên quan
        $relatedBlogs = $this->blogService->getRelatedBlogs($blog);

        return ApiResponse::success('Lấy chi tiết bài viết thành công', 200, [
            'blog' => new BlogResource($blog),
            'related_blogs' => BlogResource::collection($relatedBlogs),
        ]);
    }


    public function topViewed(): JsonResponse
    {
        $blogs = $this->blogService->topViewed();
        return ApiResponse::success('Top bài viết nổi bật', 200, BlogResource::collection($blogs)->toArray(request()));
    }

    public function latest(): JsonResponse
    {
        $blogs = $this->blogService->latestBlogs();
        return ApiResponse::success('Top bài viết mới nhất', 200, BlogResource::collection($blogs)->toArray(request()));
    }
}
