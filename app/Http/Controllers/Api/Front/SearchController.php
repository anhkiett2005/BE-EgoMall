<?php

namespace App\Http\Controllers\Api\Front;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Response\ApiResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $request = request();

            $dataSearch = Product::with(['variants' => function($query) {
                                    $query->where('is_active', '!=', 0)
                                          ->with(['orderDetails.review']);
                                }])
                                ->where('is_active', '!=', 0)
                                ->where('name', 'like', '%' . $request->search . '%')
                                ->get();

            // Xử lý dữ liệu trả về fe
            $listProduct = collect();

            foreach ($dataSearch as $product) {
                $firstVariant = $product->variants->sortBy('price')->first(); // Lấy variant đầu tiên

                $avgRating = 0;
                $soldCount = 0;
                $totalReviews = 0;

                foreach ($product->variants as $variant) {
                    foreach ($variant->orderDetails as $orderDetail) {
                        if ($orderDetail->review) {
                            $avgRating += $orderDetail->review->rating;
                            $totalReviews++;
                        }
                        $soldCount += $orderDetail->quantity;
                    }
                }

                $avgRating = $totalReviews > 0 ? $avgRating / $totalReviews : 0;

                $listProduct->push([
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'image' => $product->image,
                    'avg_rating' => $avgRating ?? 0,
                    'sold_count' => $soldCount ?? 0,
                    'price' => $firstVariant?->price ?? 0,
                    'sale_price' => $firstVariant?->sale_price ?? 0,
                ]);
            }

            return ApiResponse::success('Tìm kiếm sản phẩm thành công!!',data: $listProduct);
        }catch (\Exception $e) {
            logger('Log bug search product in front',[
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra !!!');
        }
    }
}
