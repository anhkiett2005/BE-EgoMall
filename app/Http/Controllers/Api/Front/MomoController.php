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

        $payUrl = Common::momoPayment($order); // <- string

        if (empty($payUrl)) {
            throw new ApiException('Tạo link thanh toán MoMo thất bại!', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success(
            message: 'success',
            data: ['redirect_url' => $payUrl]
        );
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
        $baseId    = $request->input('extraData') ?: explode('-', (string)$orderId)[0];
        logger('baseRequestMoMo', [
            'data' => $request->all(),
        ]);

        // Giao diện phía client sẽ xử lý giao diện hiển thị
        if ($resultCode == 0) {
            // return ApiResponse::success('Thanh toán thành công!',data: [
            //     'order_id' => $orderId
            // ]);

            return redirect()->away(env('FRONTEND_URL') . "/payment-result?status=success&order_id=" . $baseId);
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

        logger([
            'accessKey' => $accessKey,
            'secretKey' => $secretKey,
            'data' => $data
        ]);

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

        $baseOrderId = $data['extraData'] ?? null;
        if (!$baseOrderId) {
            // fallback nếu thiếu extraData: tách trước dấu '-'
            $baseOrderId = explode('-', (string)$data['orderId'])[0] ?? '';
        }

        // Tìm đơn hàng và cập nhật trạng thái
        $order = Order::where('unique_id', $baseOrderId)->first();

        if (!$order) {
            throw new ApiException('Không tìm thấy đơn hàng!', Response::HTTP_NOT_FOUND);
        }

        if ($data['resultCode'] == 0) {
            // Chặn nếu đơn đã hủy
            if ($order->status === 'cancelled') {
                logger('MoMo IPN ignored - order cancelled', ['order_id' => $order->unique_id]);
                return ApiResponse::success('IPN ignored: order cancelled');
            }

            if (in_array($order->payment_status, ['paid', 'refunded'], true)) {
                logger('MoMo IPN ignored - payment finalized', ['order_id' => $order->unique_id]);
                return ApiResponse::success('IPN ignored: payment already finalized');
            }

            if ((int)$data['amount'] !== (int)$order->total_price) {
                logger('MoMo IPN amount mismatch', [
                    'order_id' => $order->unique_id,
                    'ipn_amount' => $data['amount'],
                    'order_amount' => $order->total_price,
                ]);
                throw new ApiException('Số tiền IPN không khớp!', Response::HTTP_BAD_REQUEST);
            }


            $order->update([
                'payment_status' => 'paid',
                'payment_date'   => now(),
                'transaction_id' => $data['transId'],
            ]);


            Common::sendOrderStatusMail($order, 'ordered');
        }

        return ApiResponse::success('IPN processed successfully');
    }
}
