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
                // Xoá ảnh cũ nếu có
                if (!empty($blog->image_url)) {
                    $publicId = Common::getCloudinaryPublicIdFromUrl($blog->image_url);
                    if ($publicId) {
                        Common::deleteImageFromCloudinary($publicId);
                    }
                }

                // Upload ảnh mới
                $data['image_url'] = Common::uploadImageToCloudinary(
                    request()->file('image_url'),
                    'egomall/blogs'
                );
            }

            // Sinh slug mới nếu chưa có hoặc cần sinh lại
            $data['slug'] = Str::slug($data['slug'] ?? $data['title']);

            // Kiểm tra trùng slug (trừ chính bài viết hiện tại)
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
            // ->where('published_at', '<=', now())
            ->latest();

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return $query->get();
    }

    public function showBySlug(string $slug): Blog
    {
        $blog = Blog::with([
            'category',
            'creator',
            'products' => function ($q) {
                $q->where('is_active', '!=', 0)
                    ->whereHas('variants', function ($v) {
                        $v->where('is_active', '!=', 0);
                    })
                    ->with([
                        'category',
                        'brand',
                        'variants' => function ($v) {
                            $v->where('is_active', '!=', 0)
                                ->with(['values', 'orderDetails.review']);
                        }
                    ]);
            },
        ])
            ->where('slug', $slug)
            ->where('status', 'published')
            // ->where('published_at', '<=', now())
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

        if ($blog->relationLoaded('products') && $blog->products->isNotEmpty()) {
            $productIds = $blog->products->pluck('id');

            // Tổng hợp rating (chỉ review approved) theo product_id
            $ratingAgg = DB::table('product_variants as pv')
                ->join('order_details as od', 'od.product_variant_id', '=', 'pv.id')
                ->join('reviews as r', 'r.order_detail_id', '=', 'od.id')
                ->select(
                    'pv.product_id',
                    DB::raw('AVG(r.rating) as avg_rating'),
                    DB::raw('COUNT(r.id) as review_count')
                )
                ->whereIn('pv.product_id', $productIds)
                ->where('r.status', 'approved') // CHỈNH: đồng bộ với luồng product
                ->groupBy('pv.product_id')
                ->get()
                ->keyBy('product_id');

            // Tổng hợp sold_count theo product_id (đơn delivered, bỏ quà tặng)
            $soldAgg = DB::table('product_variants as pv')
                ->join('order_details as od', 'od.product_variant_id', '=', 'pv.id')
                ->join('orders as o', 'o.id', '=', 'od.order_id')
                ->select('pv.product_id', DB::raw('SUM(od.quantity) as sold_qty'))
                ->whereIn('pv.product_id', $productIds)
                ->where('o.status', 'delivered')
                ->where(function ($q) {
                    // Đồng bộ với luồng product; nếu dữ liệu cũ có NULL thì thêm OR whereNull
                    $q->where('od.is_gift', 0);
                    // $q->whereNull('od.is_gift')->orWhere('od.is_gift', 0);
                })
                ->groupBy('pv.product_id')
                ->get()
                ->keyBy('product_id');

            // Gắn attribute vào từng product để Resource dùng lại
            $blog->products->each(function ($p) use ($ratingAgg, $soldAgg) {
                $r = $ratingAgg->get($p->id);
                $s = $soldAgg->get($p->id);

                $p->setAttribute('avg_rating',   $r ? round((float)$r->avg_rating, 1) : 0.0);
                $p->setAttribute('review_count', $r ? (int)$r->review_count : 0);
                $p->setAttribute('sold_count',   $s ? (int)$s->sold_qty : 0);
            });
        }

        return $blog;
    }

    public function getRelatedBlogs(Blog $blog, int $limit = 3)
    {
        return Blog::where('category_id', $blog->category_id)
            ->where('id', '!=', $blog->id)
            ->where('status', 'published')
            // ->where('published_at', '<=', now())
            ->inRandomOrder()
            ->take($limit)
            ->get();
    }


    public function topViewed(int $limit = 4)
    {
        return Cache::remember("top_viewed_blogs_$limit", now()->addMinutes(10), function () use ($limit) {
            return Blog::with(['category', 'creator'])
                ->where('status', 'published')
                // ->where('published_at', '<=', now())
                ->orderByDesc('views')
                ->limit($limit)
                ->get();
        });
    }

    public function latestBlogs(int $limit = 1)
    {
        return Blog::with(['category', 'creator'])
            ->where('status', 'published')
            // ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }
}
