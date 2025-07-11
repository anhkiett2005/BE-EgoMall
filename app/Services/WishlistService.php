<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Product;
use App\Models\Wishlist;

class WishlistService
{
    public function listByUser(int $userId)
    {
        $products = Wishlist::with([
            'product.category',
            'product.brand',
            'product.variants.images',
            'product.variants.values.variantValue.option',
            'product.variants.orderDetails.order.review',
        ])
            ->where('user_id', $userId)
            ->latest()
            ->get()
            ->pluck('product');

        return $products;
    }

    public function add(int $userId, string $productSlug)
    {
        $product = Product::where('slug', $productSlug)->first();

        if (!$product) {
            throw new ApiException('Sản phẩm không tồn tại!', 404);
        }

        if (Wishlist::where('user_id', $userId)->where('product_id', $product->id)->exists()) {
            throw new ApiException('Sản phẩm đã có trong danh sách yêu thích!', 422);
        }

        return Wishlist::create([
            'user_id' => $userId,
            'product_id' => $product->id,
        ]);
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
