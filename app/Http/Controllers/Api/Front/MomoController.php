<?php

namespace App\Http\Controllers\Api\Front;

use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Jobs\SendOrderStatusMailJob;
use App\Models\Order;
use App\Response\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MomoController extends Controller
{

    public function processPayment($order)
    {
        return Common::momoPayment($order);
    }

    public function processRefundPayment($transId, $amount)
    {
        try {
            $isRefund = Common::refundMomoTransaction($transId, $amount);

            $order = Order::where('transaction_id', $transId)->first();
            if ($isRefund['resultCode'] == 0) {
                // Hoàn lại số lượng từ đơn đã mua
                Common::restoreOrderStock($order);

                // Cập nhật lại trạng thái đơn hàng
                $order->update([
                    'status' => 'cancelled',
                    'payment_status' => 'refunded',
                    'payment_date' => now(),
                    'transaction_id' => $isRefund['transId']
                ]);
            }

            return ApiResponse::success('Hủy đơn hàng thành công!!');
        } catch (ApiException) {
            return ApiResponse::error('Có lỗi xảy ra, vui lòng liên hệ administrator!!');
        } catch (\Exception $e) {
            logger('Log bug refund payment', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra, vui lòng liên hệ administrator!!');
        }
    }

    // Xử lý redirect từ MoMo (hiển thị kết quả cho người dùng)
    public function handleRedirect(Request $request)
    {
        $orderId = $request->input('orderId');
        $resultCode = $request->input('resultCode');

        // Giao diện phía client sẽ xử lý giao diện hiển thị
        if ($resultCode == 0) {
            // return ApiResponse::success('Thanh toán thành công!',data: [
            //     'order_id' => $orderId
            // ]);

            return redirect()->away(env('FRONTEND_URL') . "/payment-result?status=success&order_id=" . $orderId);
        } else {
            return redirect()->away(env('FRONTEND_URL') . "/payment-result?status=failed");
        }
    }

    // Xử lý IPN (Thông báo trạng thái thanh toán từ server MoMo)
    public function handleIPN(Request $request)
    {
        $data = $request->all();

        // logger('MoMo IPN callback:', $data);

        // Kiểm tra chữ ký hợp lệ
        $accessKey = env('MOMO_ACCESS_KEY');
        $secretKey = env('MOMO_SECRET_KEY');

        $rawHash = "accessKey={$accessKey}" .
            "&amount={$data['amount']}" .
            "&extraData={$data['extraData']}" .
            "&message={$data['message']}" .
            "&orderId={$data['orderId']}" .
            "&orderInfo={$data['orderInfo']}" .
            "&orderType={$data['orderType']}" .
            "&partnerCode={$data['partnerCode']}" .
            "&payType={$data['payType']}" .
            "&requestId={$data['requestId']}" .
            "&responseTime={$data['responseTime']}" .
            "&resultCode={$data['resultCode']}" .
            "&transId={$data['transId']}";


        $signature = hash_hmac("sha256", $rawHash, $secretKey);

        if ($signature !== $data['signature']) {
            logger("MoMo signature mismatch", ['expected' => $signature, 'received' => $data['signature']]);
            throw new ApiException('Chữ ký không hợp lệ!', Response::HTTP_BAD_REQUEST);
        }

        // Tìm đơn hàng và cập nhật trạng thái
        $order = Order::where('unique_id', $data['orderId'])->first();

        if (!$order) {
            throw new ApiException('Không tìm thấy đơn hàng!', Response::HTTP_NOT_FOUND);
        }

        if ($data['resultCode'] == 0) {
            $mailStatus = $order->mail_status ?? [];

            // Gửi mail sau khi thanh toán thành công MOMO
            if (empty($mailStatus['ordered'])) {
                SendOrderStatusMailJob::dispatch($order, 'ordered');
                $mailStatus['ordered'] = true;
            }

            $order->update([
                'payment_status' => 'paid',
                'payment_date' => now(),
                'transaction_id' => $data['transId'],
                'mail_status' => $mailStatus,
            ]);
        }

        return ApiResponse::success('IPN processed successfully');
    }
}
