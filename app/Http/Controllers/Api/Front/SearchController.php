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
            $term = trim((string) $request->input('search', ''));

            $now = now();

            $finalPriceSub = DB::table('product_variants as pv2')
                ->selectRaw("
                MIN(
                    COALESCE(
                        pv2.sale_price,
                        pv2.price - COALESCE((
                            SELECT CASE
                                WHEN p2.promotion_type = 'percentage' THEN pv2.price * (p2.discount_value / 100)
                                WHEN p2.promotion_type = 'fixed_amount' THEN p2.discount_value
                                ELSE 0
                            END
                            FROM promotions p2
                            JOIN promotion_product pp2 ON p2.id = pp2.promotion_id
                            WHERE pp2.product_variant_id = pv2.id
                              AND p2.status = 'active'
                              AND p2.start_date <= ?
                              AND p2.end_date >= ?
                            ORDER BY p2.discount_value DESC
                            LIMIT 1
                        ), 0)
                    )
                )
            ", [$now, $now])
                ->whereColumn('pv2.product_id', 'p.id')
                ->where('pv2.is_active', '!=', 0);

            $products = DB::table('products as p')
                ->join('product_variants as pv', 'pv.product_id', '=', 'p.id')
                ->leftJoin('order_details as od', 'od.product_variant_id', '=', 'pv.id')
                ->leftJoin('orders as o', 'o.id', '=', 'od.order_id')
                ->leftJoin('reviews as r', 'r.order_detail_id', '=', 'od.id')
                ->where('p.is_active', '!=', 0)
                ->where('pv.is_active', '!=', 0)
                ->when($term !== '', function ($q) use ($term) {
                    $q->where('p.name', 'like', "%{$term}%");
                })
                ->groupBy('p.id', 'p.name', 'p.slug', 'p.image')
                ->select([
                    'p.id',
                    'p.name',
                    'p.slug',
                    'p.image',
                ])
                ->selectSub($finalPriceSub, 'final_price')
                ->addSelect([
                    // CHỈNH: sold_count — giữ số, không bọc COALESCE ngoài cùng +0 để MySQL trả số
                    DB::raw("
            SUM(
                CASE
                    WHEN o.status = 'delivered' AND (od.is_gift = 0 OR od.is_gift IS NULL)
                    THEN od.quantity ELSE 0
                END
            ) as sold_count
        "),
                    // CHỈNH: đổi alias avg_rating -> average_rating, chỉ review approved
                    DB::raw("
            ROUND(AVG(CASE WHEN r.status = 'approved' THEN r.rating END), 1) as average_rating
        "),
                    // CHỈNH: review_count đã là COUNT -> alias đúng tên
                    DB::raw("
            COUNT(CASE WHEN r.status = 'approved' THEN r.id END) as review_count
        "),
                ])
                ->get()
                // CHỈNH: ép kiểu chắc chắn ra số ở JSON
                ->map(function ($row) {
                    $row->average_rating = (float) ($row->average_rating ?? 0);
                    $row->review_count   = (int)   ($row->review_count ?? 0);
                    $row->sold_count     = (int)   ($row->sold_count ?? 0);
                    return $row;
                });

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