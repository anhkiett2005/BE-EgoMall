<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Product;
use App\Models\Wishlist;

class WishlistService
{
    public function listByUser(int $userId)
    {
        try {
            return Wishlist::with([
                'product.category',
                'product.brand',
                'product.variants.images',
            ])
                ->where('user_id', $userId)
                ->whereHas('product', fn($q) => $q->where('is_active', '!=', 0)) // chỉ lấy sp đang active
                ->latest()
                ->get()
                ->pluck('product');
        } catch (\Exception $e) {
            logger('Wishlist:listByUser error', [
                'error_message' => $e->getMessage(),
                'error_file'    => $e->getFile(),
                'error_line'    => $e->getLine(),
                'stack_trace'   => $e->getTraceAsString(),
            ]);
            throw new ApiException('Không thể lấy danh sách wishlist!', 500);
        }
    }


    public function add(int $userId, string $productSlug)
    {
        try {
            $product = Product::where('slug', $productSlug)
                ->where('is_active', '!=', 0) // không cho add sp ngừng hoạt động
                ->first();

            if (!$product) {
                throw new ApiException('Sản phẩm không tồn tại!', 404);
            }

            $wishlist = Wishlist::firstOrCreate([
                'user_id'    => $userId,
                'product_id' => $product->id,
            ]);

            if (!$wishlist->wasRecentlyCreated) {
                throw new ApiException('Sản phẩm đã có trong danh sách yêu thích!', 422);
            }

            return $wishlist;
        } catch (\Exception $e) {
            logger('Wishlist:add error', [
                'slug'          => $productSlug,
                'user_id'       => $userId,
                'error_message' => $e->getMessage(),
                'error_file'    => $e->getFile(),
                'error_line'    => $e->getLine(),
                'stack_trace'   => $e->getTraceAsString(),
            ]);
            // Nếu là lỗi unique index DB cũng ném về 422
            if (method_exists($e, 'getCode') && (int)$e->getCode() === 23000) {
                throw new ApiException('Sản phẩm đã có trong danh sách yêu thích!', 422);
            }
            throw $e instanceof ApiException ? $e : new ApiException('Không thể thêm wishlist!', 500);
        }
    }


    public function remove(int $userId, string $productSlug)
    {
        $product = Product::where('slug', $productSlug)->first();

        if (!$product) {
            throw new ApiException('Sản phẩm không tồn tại!', 404);
        }

        $wishlist = Wishlist::where('user_id', $userId)
            ->where('product_id', $product->id)
            ->first();

        if (!$wishlist) {
            throw new ApiException('Không tìm thấy sản phẩm trong wishlist!', 404);
        }

        return $wishlist->delete();
    }
}
