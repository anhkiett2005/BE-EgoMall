<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BlogRequest;
use App\Http\Resources\Admin\BlogResource;
use App\Response\ApiResponse;
use App\Services\BlogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    protected BlogService $blogService;

    public function __construct(BlogService $blogService)
    {
        $this->blogService = $blogService;
    }

    public function index(Request $request): JsonResponse
    {
        $blogs = $this->blogService->listAllForAdmin($request->all());
        return ApiResponse::success('Lấy danh sách bài viết thành công', 200, BlogResource::collection($blogs)->toArray($request));
    }

    public function show(string $id): JsonResponse
    {
        $blog = $this->blogService->show((int) $id);
        return ApiResponse::success('Lấy chi tiết bài viết thành công', 200, (new BlogResource($blog))->toArray(request()));
    }

    public function store(BlogRequest $request): JsonResponse
    {
        $blog = $this->blogService->create($request->validated());
        return ApiResponse::success('Tạo bài viết thành công', 201, (new BlogResource($blog))->toArray(request()));
    }

    public function update(BlogRequest $request, string $id): JsonResponse
    {
        $blog = $this->blogService->update((int) $id, $request->validated());
        return ApiResponse::success('Cập nhật bài viết thành công', 200);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->blogService->delete((int) $id);
        return ApiResponse::success('Xóa bài viết thành công', 200);
    }

    public function restore(string $id): JsonResponse
    {
        $blog = $this->blogService->restore((int) $id);
        return ApiResponse::success('Khôi phục bài viết thành công', 200);
    }
}
