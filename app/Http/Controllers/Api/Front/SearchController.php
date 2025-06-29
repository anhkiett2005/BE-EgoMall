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
                                    $query->where('is_active', '!=', 0);
                                }])
                                ->where('is_active', '!=', 0)
                                ->where('name', 'like', '%' . $request->search . '%')
                                ->get();

            // Xử lý dữ liệu trả về fe
            $listProduct = collect();

            foreach ($dataSearch as $product) {
                $firstVariant = $product->variants->first(); // Lấy variant đầu tiên

                $listProduct->push([
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'image' => $product->image,
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
