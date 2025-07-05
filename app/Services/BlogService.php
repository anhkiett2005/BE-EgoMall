<?php

namespace App\Services;

use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Models\Blog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;


class BlogService
{
    // DÙNG CHO ADMIN
    public function listAllForAdmin(array $filters = [])
    {
        $query = Blog::with(['category', 'creator'])->latest();

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return $query->get();
    }


    public function show(int $id): Blog
    {
        $blog = Blog::with(['category', 'creator'])->find($id);

        if (!$blog) {
            throw new ApiException('Không tìm thấy bài viết', 404);
        }

        return $blog;
    }

    public function create(array $data): Blog
    {
        try {
            if (request()->hasFile('image_url')) {
                $data['image_url'] = Common::uploadImageToCloudinary(request()->file('image_url'), 'egomall/blogs');
            }

            $data['slug'] = $data['slug'] ?? Str::slug($data['title']);
            $data['created_by'] = Auth::id();

            return Blog::create($data);
        } catch (\Exception $e) {
            throw new ApiException('Tạo bài viết thất bại!', 500, [$e->getMessage()]);
        }
    }

    public function update(int $id, array $data): Blog
    {
        $blog = Blog::find($id);

        if (!$blog) {
            throw new ApiException('Không tìm thấy bài viết để cập nhật', 404);
        }

        try {
            if (request()->hasFile('image_url')) {
                $data['image_url'] = Common::uploadImageToCloudinary(request()->file('image_url'), 'egomall/blogs');
            }

            $data['slug'] = $data['slug'] ?? Str::slug($data['title']);
            $blog->update($data);

            return $blog;
        } catch (\Exception $e) {
            throw new ApiException('Cập nhật bài viết thất bại!', 500, [$e->getMessage()]);
        }
    }

    public function delete(int $id): void
    {
        $blog = Blog::find($id);

        if (!$blog) {
            throw new ApiException('Không tìm thấy bài viết để xóa', 404);
        }

        try {
            $blog->delete();
        } catch (\Exception $e) {
            throw new ApiException('Xóa bài viết thất bại!', 500, [$e->getMessage()]);
        }
    }

    public function restore(int $id): Blog
    {
        $blog = Blog::onlyTrashed()->find($id);

        if (!$blog) {
            throw new ApiException('Không tìm thấy bài viết để khôi phục', 404);
        }

        try {
            $blog->restore();
            return $blog;
        } catch (\Exception $e) {
            throw new ApiException('Khôi phục bài viết thất bại!', 500, [$e->getMessage()]);
        }
    }

    // DÙNG CHUNG






    // DÙNG CHO uSER
    public function listPublished(array $filters = [])
    {
        $query = Blog::with(['category', 'creator'])
            ->where('status', 'published')
            ->where('is_published', true)
            ->where('published_at', '<=', now())
            ->latest();

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return $query->get();
    }


    public function showBySlug(string $slug): Blog
    {
        $blog = Blog::with(['category', 'creator'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->where('is_published', true)
            ->where('published_at', '<=', now())
            ->first();

        if (!$blog) {
            throw new ApiException('Không tìm thấy bài viết', 404);
        }

        // === Đếm lượt xem, tránh spam trong 1 tiếng ===
        /** @var \Illuminate\Contracts\Auth\Guard $auth */
        $auth = auth();
        $viewer = $auth->check()
            ? 'user_' . $auth->id()
            : 'ip_' . request()->ip();

        $cacheKey = "blog_viewed_slug_{$slug}_{$viewer}";

        if (!cache()->has($cacheKey)) {
            $blog->increment('views');
            cache()->put($cacheKey, true, now()->addMinutes(15));
        }

        return $blog;
    }

    public function topViewed(int $limit = 4)
    {
        return Blog::with(['category', 'creator'])
            ->where('status', 'published')
            ->where('is_published', true)
            ->where('published_at', '<=', now())
            ->orderByDesc('views')
            ->limit($limit)
            ->get();
    }
}