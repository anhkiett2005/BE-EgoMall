<?php
namespace App\Services;

use App\Exceptions\ApiException;

class DashboardService {

    /**
     * Thống kê trả về admin
     */

    public function statistics()
    {
        try {
            // Thống kê doanh thu theo tháng và tính tháng này tăng bnhiu so với tháng trước
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
