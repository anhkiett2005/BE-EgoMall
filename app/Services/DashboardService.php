<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\ProductVariant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{

    /**
     * Thống kê trả về admin
     */

    public function statistics()
    {
        try {
            // Thống kê doanh thu theo tháng và tính tháng này tăng bnhiu so với tháng trước

            // Lấy ngày đầu tháng này và tháng trước
            $now = Carbon::now();
            $startOfThisMonth = $now->copy()->startOfMonth();
            $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
            $endOfLastMonth = $startOfThisMonth->copy()->subSecond();
            $noww = now();
            $startOf30Days = $noww->copy()->subDays(30);


            $revenueStatistics = collect();
            $totalOrderStatistics = collect();
            $totalProductStatistics = collect();
            $totalUserStatistics = collect();
            $totalProductStatusStatistics = collect();


            // Tính doanh thu tháng hiện tại
            $thisMonthRevenue = DB::table('orders')
                ->where('status', '=', 'delivered')
                ->whereBetween('created_at', [$startOfThisMonth, $now])
                ->sum('total_price');

            // Tính doanh thu tháng trước
            $lastMonthRevenue = DB::table('orders')
                ->where('status', '=', 'delivered')
                ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                ->sum('total_price');


            // Tính phần trăm tăng trưởng & xu hướng
            $growth = 0;
            $trend = 'no_change';

            if ($lastMonthRevenue > 0) {
                $growth = (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100;

                if ($growth > 0) {
                    $trend = 'increase';
                } elseif ($growth < 0) {
                    $trend = 'decrease';
                    $growth = abs($growth); // đổi về số dương để FE dễ hiển thị
                } else {
                    $trend = 'no_change';
                }
            } elseif ($thisMonthRevenue > 0) {
                $growth = 100;
                $trend = 'increase';
            }

            // push dữ liệu trả về
            $revenueStatistics->push([
                'revenue' => (int) $thisMonthRevenue,
                'growth' => $growth,
                'trend' => $trend
            ]);

            // Tính tổng số đơn hàng
            $totalOrder = DB::table('orders')
                ->where('status', '!=', 'cancelled')
                ->count();

            // push dữ liệu trả về
            $totalOrderStatistics->push([
                'total_order' => $totalOrder
            ]);

            // Tính tổng số sản phẩm
            $totalProduct = DB::table('products')
                ->join('product_variants', 'products.id', '=', 'product_variants.product_id')
                ->where('products.is_active', '!=', 0)
                ->whereNull('products.deleted_at')
                ->where('product_variants.is_active', '!=', 0)
                ->where('product_variants.quantity', '>', 0)
                ->count('product_variants.id');

            // push dữ liệu trả về
            $totalProductStatistics->push([
                'total_product' => $totalProduct
            ]);

            // Tính tổng số người dùng
            $totalUser = DB::table('users')
                ->where('is_active', '!=', 0)
                ->count();

            // push dữ liệu trả về
            $totalUserStatistics->push([
                'total_user' => $totalUser
            ]);

            // Thống kê tổng số trạng thái của product như còn hàng, sắp hết hàng, hết hàng
            $productStatusCount = DB::table('products as p')
                ->join('product_variants as pv', 'p.id', '=', 'pv.product_id')
                ->where('p.is_active', '!=', 0)
                ->whereNull('p.deleted_at')
                ->selectRaw("
                                        SUM(CASE WHEN pv.quantity > 10 THEN 1 ELSE 0 END) as in_stock,
                                        SUM(CASE WHEN pv.quantity > 0 AND pv.quantity <= 10 THEN 1 ELSE 0 END) as low_stock,
                                        SUM(CASE WHEN pv.quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock
                                    ")
                ->where('pv.is_active', '!=', 0)
                ->first();

            // push dữ liệu trả về
            $totalProductStatusStatistics = [
                [
                    'status' => 'in_stock',
                    'label' => 'Còn hàng',
                    'total' => (int) $productStatusCount->in_stock,
                ],
                [
                    'status' => 'low_stock',
                    'label' => 'Sắp hết hàng',
                    'total' => (int) $productStatusCount->low_stock,
                ],
                [
                    'status' => 'out_of_stock',
                    'label' => 'Hết hàng',
                    'total' => (int) $productStatusCount->out_of_stock,
                ],
            ];

            // === Thống kê doanh thu 12 tháng gần nhất ===
            $now = Carbon::now()->startOfMonth();
            $start = $now->copy()->subMonths(11);

            $revenues = DB::table('orders')
                ->selectRaw("YEAR(created_at) as year, MONTH(created_at) as month, SUM(total_price) as total_revenue")
                ->where('status', 'delivered')
                ->whereBetween('created_at', [$start, $now->copy()->endOfMonth()])
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();


            $revenueLast12Months = [];
            for ($i = 0; $i < 12; $i++) {
                $target = $start->copy()->addMonths($i);
                $monthKey = $target->format('m/y');

                // Tìm trong $revenues xem có dữ liệu không
                $match = $revenues->first(function ($item) use ($target) {
                    return (int)$item->month === (int)$target->format('m') &&
                        (int)$item->year === (int)$target->format('Y');
                });

                $revenueLast12Months[] = [
                    'month' => $monthKey,
                    'total_revenue' => (float) ($match->total_revenue ?? 0),
                ];
            }

            // === Thống kê trạng thái đơn hàng trong 30 ngày gần nhất ===
            $orderStatusRaw = DB::table('orders')
                ->select('status', DB::raw('COUNT(*) as total'))
                ->where('created_at', '>=', $startOf30Days->copy()->startOfDay())
                ->where('created_at', '<=', $noww->copy()->endOfDay())
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            // Map để gán nhãn hiển thị bên FE
            $statusLabels = [
                'ordered' => 'Chờ xử lý',
                'shipping' => 'Đang giao hàng',
                'cancelled' => 'Đã hủy',
                'delivered' => 'Đã hoàn thành',
                'return_sales' => 'Trả hàng / Khiếu nại'
            ];

            // Duyệt qua các trạng thái cần hiển thị (kể cả khi số lượng = 0)
            $orderStatusStatistics = [];
            foreach ($statusLabels as $key => $label) {
                $orderStatusStatistics[] = [
                    'status' => $key,
                    'label' => $label,
                    'total' => (int) ($orderStatusRaw[$key] ?? 0)
                ];
            }
            logger('DEBUG_ORDER_STATUS_STATISTICS', [
                'from' => $startOf30Days->copy()->startOfDay()->toDateTimeString(),
                'to' => $now->copy()->endOfDay()->toDateTimeString(),
                'results' => $orderStatusRaw
            ]);

            // === Doanh thu từ đơn hàng có áp dụng khuyến mãi trong 3 tháng gần nhất ===
            $now = Carbon::now()->startOfMonth();
            $start3MonthsAgo = $now->copy()->subMonths(2); // từ 2 tháng trước tới tháng này

            $promotionRevenueRaw = DB::table('orders')
                ->selectRaw("YEAR(created_at) as year, MONTH(created_at) as month, SUM(total_price) as total_revenue")
                ->where('status', 'delivered')
                ->where(function ($query) {
                    $query->whereNotNull('coupon_id')
                        ->orWhereRaw("JSON_EXTRACT(discount_details, '$.totalFlashSale') > 0");
                })
                ->whereBetween('created_at', [$start3MonthsAgo, $now->copy()->endOfMonth()])
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();

            $promotionRevenueLast3Months = [];
            for ($i = 0; $i < 3; $i++) {
                $target = $start3MonthsAgo->copy()->addMonths($i);
                $monthKey = $target->format('m/y');

                $match = $promotionRevenueRaw->first(function ($item) use ($target) {
                    return (int)$item->month === (int)$target->format('m') &&
                        (int)$item->year === (int)$target->format('Y');
                });

                $promotionRevenueLast3Months[] = [
                    'month' => $monthKey,
                    'total_revenue' => (float) ($match->total_revenue ?? 0),
                ];
            }


            // === Top sản phẩm bán chạy nhất trong 3 tháng gần nhất ===
            $start3MonthsAgo = Carbon::now()->startOfMonth()->subMonths(2); // tính từ 2 tháng trước tới tháng này

            $topProductsRaw = DB::table('order_details as od')
                ->join('orders as o', 'od.order_id', '=', 'o.id')
                ->join('product_variants as pv', 'od.product_variant_id', '=', 'pv.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->selectRaw('pv.product_id, MAX(p.name) as product_name, SUM(od.quantity) as total_sold')
                ->where('o.status', 'delivered')
                ->whereBetween('o.created_at', [$start3MonthsAgo, now()])
                ->where('p.is_active', '!=', 0)
                ->whereNull('p.deleted_at')
                ->groupBy('pv.product_id')
                ->orderByDesc('total_sold')
                ->limit(10)
                ->get();

            $topSellingProducts = $topProductsRaw->map(function ($item) {
                return [
                    'product_name' => $item->product_name,
                    'total_sold' => (int) $item->total_sold,
                ];
            });


            // === Danh sách sản phẩm sắp hết hàng (tồn kho biến thể <= 10) ===
            $rawLowStockVariants = DB::table('product_variants as pv')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->where('pv.is_active', 1)
                ->where('p.is_active', 1)
                ->whereNull('p.deleted_at')
                ->where('pv.quantity', '>', 0)
                ->where('pv.quantity', '<=', 10)
                ->select('pv.id as variant_id', 'pv.quantity', 'p.name as product_name')
                ->orderBy('pv.quantity', 'asc')
                ->get();

            // Lấy chi tiết các variant để ghép tên biến thể
            $variantIds = $rawLowStockVariants->pluck('variant_id');
            $variants = ProductVariant::with(['values.option'])
                ->whereIn('id', $variantIds)
                ->get()
                ->keyBy('id');

            // Format kết quả trả về
            $lowStockList = $rawLowStockVariants->map(function ($item) use ($variants) {
                $variant = $variants[$item->variant_id];
                return [
                    'variant_id' => $item->variant_id,
                    'product_name' => $item->product_name,
                    'variant_values' => $variant->variant_name ?? '', // gọi accessor
                    'quantity' => (int) $item->quantity,
                ];
            });

            $data = [
                'revenue_statistics' => $revenueStatistics,
                'total_order_statistics' => $totalOrderStatistics,
                'total_product_statistics' => $totalProductStatistics,
                'total_user_statistics' => $totalUserStatistics,
                'total_product_status_statistics' => $totalProductStatusStatistics,
                'revenue_last_12_months' => $revenueLast12Months,
                'order_status_statistics' => $orderStatusStatistics,
                'promotion_revenue_last_3_months' => $promotionRevenueLast3Months,
                'top_selling_products_last_3_months' => $topSellingProducts,
                'low_stock_products' => $lowStockList,
            ];

            return $data;
        } catch (\Exception $e) {
            logger('Log bug dashboard', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!', 500);
        }
    }
}
