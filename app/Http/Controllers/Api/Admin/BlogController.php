<?php

namespace App\Http\Controllers\Api\Admin;

use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\BlogRequest;
use App\Models\Blog;
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
        $categoryId = $request->query('category_id');

        if ($categoryId) {
            $blogs = Blog::with(['category', 'creator'])
                ->where('category_id', $categoryId)
                ->get();

            return ApiResponse::success('Lọc bài viết theo danh mục thành công', 200, $blogs);
        }

        $blogs = Blog::with(['category', 'creator'])->latest()->get();
        return ApiResponse::success('Lấy danh sách bài viết thành công', 200, $blogs);
    }



    public function show(string $id): JsonResponse
    {
        $blog = $this->blogService->show((int) $id);
        return ApiResponse::success('Lấy chi tiết bài viết thành công', 200, $blog->toArray());
    }

    public function store(BlogRequest $request): JsonResponse
    {
        $blog = $this->blogService->create($request->validated());
        return ApiResponse::success('Tạo bài viết thành công', 201, $blog->toArray());
    }

    public function update(BlogRequest $request, string $id): JsonResponse
    {
        $blog = $this->blogService->update((int) $id, $request->validated());
        return ApiResponse::success('Cập nhật bài viết thành công', 200, [
            'blog' => $blog,
        ]);
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

    public function topViewed(): JsonResponse
    {
        $blogs = $this->blogService->topViewed();
        return ApiResponse::success('Top bài viết nổi bật', 200, $blogs);
    }
}
