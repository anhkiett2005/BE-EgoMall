<?php

namespace App\Http\Controllers\Api\Front;

use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Response\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MomoController extends Controller
{

    public function processPayment($orderId, $amount) {
        return Common::momoPayment($orderId, $amount);
    }

    // Xử lý redirect từ MoMo (hiển thị kết quả cho người dùng)
    public function handleRedirect(Request $request)
    {
        $orderId = $request->input('orderId');
        $resultCode = $request->input('resultCode');

        // Giao diện phía client sẽ xử lý giao diện hiển thị
        if ($resultCode == 0) {
            return ApiResponse::success('Thanh toán thành công!',data: [
                'order_id' => $orderId
            ]);
        } else {
            return ApiResponse::error('Thanh toán thất bại hoặc bị hủy!',Response::HTTP_BAD_REQUEST,[
                'order_id' => $orderId
            ]);
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
            $order->update([
                'payment_status' => 'paid',
                'payment_date' => now(),
                'transaction_id' => $data['transId']
            ]);
        }

        return ApiResponse::success('IPN processed successfully');
    }
}
