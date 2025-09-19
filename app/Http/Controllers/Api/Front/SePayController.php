<?php

namespace App\Http\Controllers\Api\Front;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SystemSetting;
use App\Response\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SePayController extends Controller
{
    public function processPayment(Order $order)
    {
        try {
            // Check trong cấu hình bank trong Setting
            $settings = SystemSetting::where('setting_group', 'bank')
                                    ->whereIn('setting_key', ['bank_name', 'bank_account'])
                                    ->pluck('setting_value', 'setting_key');

            if (empty($settings['bank_name']) || empty($settings['bank_account'])) {
                throw new ApiException('Có lỗi xảy ra, vui lòng liên hệ administrator!!', Response::HTTP_BAD_REQUEST);
            }

            // Tạo qr code thanh toán với SePay
            $qrCode = "https://qr.sepay.vn/img?" . http_build_query([
                'acc'      => $settings['bank_account'],
                'bank'     => $settings['bank_name'],
                'amount'   => $order->total_price,
                'des'      => "Thanh toan don hang " . $order->unique_id,
                'template' => 'compact',
                'download' => 0,
            ]);

            return ApiResponse::success('Tạo qr code thanh toán thành công.', data: [
                'qr_code' => $qrCode
            ]);
        }catch (ApiException $e) {
            throw $e;
        }catch (\Exception $e) {
            logger('Log bug sepay transaction', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra trong quá trình tạo thanh toán!!');
        }
    }
}
