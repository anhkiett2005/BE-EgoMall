<?php

namespace App\Http\Controllers\Api\Front;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Promotion;
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

            // ====== PHẦN SQL GỐC CỦA BẠN (GIỮ NGUYÊN) ======
            $rows = DB::table('products as p')
                ->join('product_variants as pv', 'pv.product_id', '=', 'p.id')
                ->leftJoin('order_details as od', 'od.product_variant_id', '=', 'pv.id')
                ->leftJoin('orders as o', 'o.id', '=', 'od.order_id')
                ->leftJoin('reviews as r', 'r.order_detail_id', '=', 'od.id')
                ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
                ->where('p.is_active', '!=', 0)
                ->where('pv.is_active', '!=', 0)
                ->when($term !== '', function ($q) use ($term) {
                    $q->where('p.name', 'like', "%{$term}%");
                })
                ->groupBy('p.id', 'p.name', 'p.slug', 'p.image', 'b.name')
                ->select([
                    'p.id',
                    'p.name',
                    'p.slug',
                    'p.image',
                    'b.name as brand_name',
                ])
                // ->selectSub($finalPriceSub, 'final_price')
                ->addSelect([
                    // CHỈNH: sold_count — chỉ tính đơn delivered, bỏ quà tặng/NULL
                    DB::raw("
                    SUM(
                        CASE
                            WHEN o.status = 'delivered' AND (od.is_gift = 0 OR od.is_gift IS NULL)
                            THEN od.quantity ELSE 0
                        END
                    ) as sold_count
                "),
                    // CHỈNH: average_rating — chỉ review approved
                    DB::raw("
                    ROUND(AVG(CASE WHEN r.status = 'approved' THEN r.rating END), 1) as average_rating
                "),
                    // CHỈNH: review_count — chỉ review approved
                    DB::raw("
                    COUNT(CASE WHEN r.status = 'approved' THEN r.id END) as review_count
                "),
                ])
                ->get();

            // ====== CHỈNH: Nạp Eloquent để lấy variants + tính final_price_discount ======
            $productIds = $rows->pluck('id');

            $eloquentProducts = Product::with([
                'brand',
                'variants' => function ($q) {
                    $q->where('is_active', '!=', 0)
                        ->with(['values.option', 'images']); // CHỈNH: load quan hệ cần thiết
                }
            ])->whereIn('id', $productIds)->get()->keyBy('id');

            // CHỈNH: Lấy danh sách promotions đang hoạt động để tính giảm giá
            $promotions = $this->getActivePromotions();

            // CHỈNH: Gộp dữ liệu SQL (tổng hợp) + Eloquent (variants)
            $products = $rows->map(function ($row) use ($eloquentProducts, $promotions) {
                $p = $eloquentProducts->get($row->id);

                $variants = $p
                    ? $p->variants->map(function ($variant) use ($promotions) {
                        return [
                            'id'                   => $variant->id,
                            'sku'                  => $variant->sku,
                            'price'                => (float) $variant->price,
                            'sale_price'           => $variant->sale_price !== null ? (float) $variant->sale_price : null,
                            // CHỈNH: giống ProductController — trả về GIÁ SAU GIẢM nếu đang có promotion (và sale_price = null)
                            'final_price_discount' => $this->checkPromotion($variant, $promotions),
                        ];
                    })->values()
                    : collect();

                return [
                    'id'              => (int) $row->id,
                    'name'            => $row->name,
                    'slug'            => $row->slug,
                    'image'           => $row->image,
                    // CHỈNH: brand = name (đồng bộ format)
                    'brand'           => [
                        'name' => $p->brand->name ?? ($row->brand_name ?? null),
                    ],
                    // Giữ min final price (nếu FE cần hiển thị nhanh)
                    // 'final_price'     => $row->final_price !== null ? (float) $row->final_price : null,
                    'sold_count'      => (int)   ($row->sold_count ?? 0),
                    'average_rating'  => (float) ($row->average_rating ?? 0),
                    'review_count'    => (int)   ($row->review_count ?? 0),

                    // CHỈNH: danh sách variants với 3 field giá đúng yêu cầu
                    'variants'        => $variants,
                ];
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

    // CHỈNH: y hệt ProductController để đồng bộ logic
    private function getActivePromotions()
    {
        $now = now();
        return Promotion::with(['products', 'productVariants'])
            ->where('status', '!=', 0)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->get();
    }

    // CHỈNH: kiểm tra promotion — trả về GIÁ SAU GIẢM (float) nếu có KM và sale_price = null; ngược lại trả null
    private function checkPromotion($variant, $promotions)
    {
        if ($variant->sale_price !== null) return null;

        // Ưu tiên promotion theo variant
        $variantPromotion = $promotions->first(function ($promo) use ($variant) {
            return $promo->productVariants->contains('id', $variant->id);
        });

        if ($variantPromotion) {
            return $this->calculateDiscount($variant, $variantPromotion);
        }

        // Nếu không, lấy promotion theo product
        $productPromotion = $promotions->first(function ($promo) use ($variant) {
            return $promo->products->contains('id', $variant->product_id);
        });

        if ($productPromotion) {
            return $this->calculateDiscount($variant, $productPromotion);
        }

        return null;
    }

    // CHỈNH: tính giá sau giảm theo loại promotion
    private function calculateDiscount($variant, $promotion)
    {
        if ($variant->sale_price !== null) {
            return null;
        }

        $price = (float) $variant->price;
        $discount = 0;

        if ($promotion->promotion_type === 'percentage') {
            $discount = $price * ($promotion->discount_value / 100);
        } elseif ($promotion->promotion_type === 'fixed_amount') {
            $discount = $promotion->discount_value;
        }

        return max(0, $price - $discount);
    }
}