<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Response\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CodController extends Controller
{
    public function processConfirmPayment(string $uniqueId) {
        try {
            $order = Order::where('unique_id', $uniqueId)->first();

            if(is_null($order)) {
                throw new ApiException("Không tìm thấy đơn hàng trong hệ thống với mã: {$uniqueId}.");
            }

            // check nếu payment_method khác COD thì throw exception
            if($order->payment_method != 'COD') {
                throw new ApiException("Không thể xác nhận thanh toán. Đơn hàng {$uniqueId} không sử dụng phương thức COD.");
            }

            // check nếu order dc xác nhận rồi thì throw exception
            if($order->payment_status == 'paid') {
                throw new ApiException("Đơn hàng {$uniqueId} đã được thanh toán trước đó.");
            }

            $order->update([
                'payment_status' => 'paid',
                'payment_date' => now(),
            ]);

            return ApiResponse::success('Xác nhận thanh toán thành công cho đơn hàng ' . $order->unique_id);
        } catch(\Exception $e) {
            logger('Log bug process confirm payment cod', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            throw new ApiException('Có lỗi xảy ra!!');
        }
    }
}
