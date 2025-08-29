<?php

namespace App\Http\Controllers\Api\Front;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Response\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $request = request();

            // Lấy product kèm variants
            $products = DB::table('products as p')
                            ->join('product_variants as pv', 'pv.product_id', '=', 'p.id')
                            ->leftJoin('order_details as od', 'od.product_variant_id', '=', 'pv.id')
                            ->leftJoin('orders as o', function($join) {
                                $join->on('o.id', '=', 'od.order_id')
                                    ->where('o.status', 'delivered');
                            })
                            ->leftJoin('reviews as r', function($join){
                                $join->on('r.order_detail_id', '=', 'od.id')
                                    ->where('r.status', 'approved');
                            })
                            ->select(
                                'p.id',
                                'p.name',
                                'p.slug',
                                'p.image',
                                DB::raw('MIN(pv.price) as price'),
                                DB::raw('MIN(pv.sale_price) as sale_price'),
                                DB::raw('COALESCE(SUM(CASE WHEN od.is_gift != 1 THEN od.quantity ELSE 0 END), 0) as sold_count'),
                                DB::raw('COALESCE(ROUND(AVG(r.rating), 1), 0) as avg_rating')
                            )
                            ->where('p.is_active', '!=', 0)
                            ->where('p.name', 'like', "%{$request->search}%")
                            ->groupBy('p.id', 'p.name', 'p.slug', 'p.image')
                            ->get();

            return ApiResponse::success('Tìm kiếm sản phẩm thành công!!', data: $products);
        } catch (\Exception $e) {
            logger('Log bug search product in front', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra !!!');
        }
    }
}
