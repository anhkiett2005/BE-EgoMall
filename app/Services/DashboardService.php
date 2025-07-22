<?php
namespace App\Services;

use App\Exceptions\ApiException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService {

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


            $revenueStatistics = collect();
            $totalOrderStatistics = collect();
            $totalProductStatistics = collect();
            $totalUserStatistics = collect();


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

            $data = [
                'revenue_statistics' => $revenueStatistics,
                'total_order_statistics' => $totalOrderStatistics,
                'total_product_statistics' => $totalProductStatistics,
                'total_user_statistics' => $totalUserStatistics
            ];

            return $data;
        } catch(\Exception $e) {
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
