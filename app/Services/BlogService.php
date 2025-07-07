<?php

namespace App\Services;

use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Models\Blog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class BlogService
{
    // DÙNG CHO ADMIN
    public function listAllForAdmin(array $filters = [])
    {
        $query = Blog::with(['category', 'creator', 'products'])->latest();

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return $query->get();
    }

    public function show(int $id): Blog
    {
        $blog = Blog::with(['category', 'creator', 'products'])->find($id);

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

            $data['slug'] = Str::slug($data['slug'] ?? $data['title']);
            // Kiểm tra trùng slug
            if (Blog::where('slug', $data['slug'])->exists()) {
                throw new ApiException('Slug đã tồn tại, vui lòng chọn slug khác', 422);
            }

            $data['created_by'] = auth('api')->user()->id;

            $productIds = $data['product_ids'] ?? [];
            unset($data['product_ids']);

            // Sử dụng transaction để đảm bảo đồng bộ
            $blog = DB::transaction(function () use ($data, $productIds) {
                $blog = Blog::create($data);

                if (!empty($productIds)) {
                    $blog->products()->sync($productIds);
                }

                return $blog;
            });

            return $blog;
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

            $data['slug'] = Str::slug($data['slug'] ?? $data['title']);
            // Kiểm tra trùng slug, ngoại trừ bản thân bài viết hiện tại
            if (Blog::where('slug', $data['slug'])->where('id', '!=', $id)->exists()) {
                throw new ApiException('Slug đã tồn tại, vui lòng chọn slug khác', 422);
            }

            $productIds = $data['product_ids'] ?? null;
            unset($data['product_ids']);

            DB::transaction(function () use ($blog, $data, $productIds) {
                $blog->update($data);

                // Nếu truyền lên product_ids thì cập nhật lại sản phẩm liên quan
                if (is_array($productIds)) {
                    $blog->products()->sync($productIds);
                }
            });

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

    // DÙNG CHO USER
    public function listPublished(array $filters = [])
    {
        $query = Blog::with(['category', 'creator', 'products'])
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->latest();

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return $query->get();
    }

    public function showBySlug(string $slug): Blog
    {
        $blog = Blog::with(['category', 'creator', 'products'])
            ->where('slug', $slug)
            ->where('status', 'published')
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

    public function getRelatedBlogs(Blog $blog, int $limit = 3)
    {
        return Blog::where('category_id', $blog->category_id)
            ->where('id', '!=', $blog->id)
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->inRandomOrder()
            ->take($limit)
            ->get();
    }


    public function topViewed(int $limit = 4)
    {
        return Cache::remember("top_viewed_blogs_$limit", now()->addMinutes(10), function () use ($limit) {
            return Blog::with(['category', 'creator'])
                ->where('status', 'published')
                ->where('published_at', '<=', now())
                ->orderByDesc('views')
                ->limit($limit)
                ->get();
        });
    }

    public function latestBlogs(int $limit = 4)
    {
        return Blog::with(['category', 'creator'])
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }
}