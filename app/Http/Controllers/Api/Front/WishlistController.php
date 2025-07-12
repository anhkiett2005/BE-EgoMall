<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Http\Resources\Front\WishlistProductResource;
use App\Response\ApiResponse;
use App\Services\WishlistService;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    protected $wishlistService;

    public function __construct(WishlistService $wishlistService)
    {
        $this->wishlistService = $wishlistService;
    }

    public function index(Request $request)
    {
        $userId = auth('api')->id();

        $products = $this->wishlistService->listByUser($userId);

        return ApiResponse::success('Lấy wishlist thành công', 200, WishlistProductResource::collection($products)->toArray($request));
    }

    public function store(Request $request)
    {
        $userId = auth('api')->id();
        $slug = $request->input('product_slug');

        if (!$slug) {
            return ApiResponse::error('Thiếu product_slug!', 422);
        }

        $this->wishlistService->add($userId, $slug);

        return ApiResponse::success('Thêm vào wishlist thành công');
    }

    public function destroy($productSlug)
    {
        $userId = auth('api')->id();

        $this->wishlistService->remove($userId, $productSlug);

        return ApiResponse::success('Xóa khỏi wishlist thành công');
    }
}
