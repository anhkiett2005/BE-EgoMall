<?php

namespace App\Http\Controllers\Api\Front;

use App\Classes\Common;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Response\ApiResponse;
use Illuminate\Http\Request;

class CodController extends Controller
{
    public function processPayment(Order $order)
    {
        try {
            $order->update([
                'payment_created_at' => now(),
                'transaction_id' => Common::generateCodTransactionId()
            ]);

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
}
