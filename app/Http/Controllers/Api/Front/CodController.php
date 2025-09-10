<?php

namespace App\Http\Controllers\Api\Front;

use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Response\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CodController extends Controller
{
    public function processPayment(Order $order)
    {
        try {
            $order->update([
                'payment_created_at' => now(),
                'transaction_id' => Common::generateCodTransactionId()
            ]);

            Common::sendOrderStatusMail($order, 'ordered');

            return ApiResponse::success(data: [
                'redirect_url' => env('FRONTEND_URL') . '/payment-result?status=success&order_id=' . $order->unique_id
            ]);
        } catch (\Exception $e) {
            logger('Log bug process payment cod', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function processRefundCancelOrderPayment(Order $order, $request)
    {
        try {
            $order->update([
                    'status'         => 'cancelled',
                    'payment_status' => 'cancelled',
                    'payment_date'   => now(),
                    'reason'  => $request->reason,
            ]);

            // Hoàn lại số lượng sản phẩm
            Common::restoreOrderStock($order);

            // Hoàn lại voucher
            Common::revertVoucherUsageInline($order);

            // Gửi mail hủy đơn hàng
            Common::sendOrderStatusMail($order, 'cancelled');

            return ApiResponse::success('Hủy đơn hàng thành công!', data: [
                'order_id'       => $order->unique_id,
                'status'         => $order->status,
            ]);
        } catch (\Exception $e) {
            logger('Log bug refund cancel order payment', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra, vui lòng liên hệ administrator!!');
        }
    }
}
