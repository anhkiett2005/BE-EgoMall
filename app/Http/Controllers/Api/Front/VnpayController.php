<?php

namespace App\Http\Controllers\Api\Front;

use App\Classes\Common;
use App\Enums\VnPayStatus;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Jobs\SendOrderStatusMailJob;
use App\Models\FinancialTransaction;
use App\Models\Order;
use App\Response\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class VnPayController extends Controller
{
    protected $vnp_TmnCode;
    protected $vnp_HashSecret;
    protected $vnp_Url;
    protected $vnp_ReturnUrl;

    protected $vnp_apiUrl;
    protected $apiUrl;

    public function __construct()
    {
        $this->vnp_TmnCode = env('VNP_TMN_CODE'); // Mã định danh merchant
        $this->vnp_HashSecret = env('VNP_HASH_SECRECT_KEY'); // Secret key (Sửa lỗi sai: SECRECT -> SECRET)
        $this->vnp_Url = env('VNP_URL');
        $this->vnp_ReturnUrl = route('payment.vnpay.redirect'); // Đổi sang route để dễ quản lý
        $this->vnp_apiUrl = env('VNP_API_URL');
        $this->apiUrl = env('API_URL');
    }

    public function processPayment(Order $order)
    {
        try {
            $startTime = date("YmdHis");
            $expire = date('YmdHis', strtotime('+15 minutes', strtotime($startTime)));


            $vnp_TxnRef = $order->unique_id;
            $vnp_Amount = $order->total_price;
            $vnp_Locale = 'vn';
            $vnp_BankCode = "NCB";
            $vnp_IpAddr = request()->ip();

            $inputData = [
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => $this->vnp_TmnCode,
                "vnp_Amount" => $vnp_Amount * 100,
                "vnp_Command" => "pay",
                "vnp_CreateDate" => date('YmdHis'),
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => $vnp_IpAddr,
                "vnp_Locale" => $vnp_Locale,
                "vnp_OrderInfo" => "Thanh toán GD:" . $vnp_TxnRef,
                "vnp_OrderType" => "other",
                "vnp_ReturnUrl" => $this->vnp_ReturnUrl,
                "vnp_TxnRef" => $vnp_TxnRef,
                "vnp_ExpireDate" => $expire,
            ];

            if (!empty($vnp_BankCode)) {
                $inputData['vnp_BankCode'] = $vnp_BankCode;
            }

            ksort($inputData);
            $query = "";
            $hashdata = "";
            foreach ($inputData as $key => $value) {
                $hashdata .= ($hashdata ? '&' : '') . urlencode($key) . "=" . urlencode($value);
                $query .= urlencode($key) . "=" . urlencode($value) . "&";
            }

            $vnp_Url = $this->vnp_Url . "?" . $query;
            if (!empty($this->vnp_HashSecret)) {
                $vnpSecureHash = hash_hmac('sha512', $hashdata, $this->vnp_HashSecret);
                $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
            }

            // $paymentUrl = $this->vnp_Url . "?" . $query; Front-end

            // Lưu lại thời gian tạo thanh toán
            $order->payment_created_at = Carbon::now();
            $order->save();

            return ApiResponse::success('Link thanh toán được tạo thành công.',data: [
                'redirect_url' => $vnp_Url
            ]);
        } catch (\Exception $e) {
            logger('Log bug vnpay transaction', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra trong quá trình tạo thanh toán!!');
        }
    }

    public function handleRedirect(Request $request)
    {
        try {

            $statusEnum = VnPayStatus::tryFrom($request->vnp_ResponseCode);

            $code = $statusEnum?->value ?? $request->vnp_ResponseCode;
            $message = VnPayStatus::description($code);


            if (!Common::validateSignature($request->all(), $this->vnp_HashSecret)) {
                // return redirect()->away(env('FRONTEND_URL') . '/payment-result?status=failed');
                // return ApiResponse::error('Xác thực thất bại', Response::HTTP_BAD_REQUEST);
                return redirect()->away(env('FRONTEND_URL') . "/profile/orders?" . http_build_query([
                    'code' => $code,
                    'message' => $message
                ]));
            }

            if ($request->vnp_ResponseCode == "00") { // Thành công
                return redirect()->away(env('FRONTEND_URL') . "/thank-you?" . http_build_query([
                    'code' => $code,
                    'message' => $message,
                    'order_id' => $request->vnp_TxnRef
                ]));

            } else {
                return redirect()->away(env('FRONTEND_URL') . "/profile/orders?" . http_build_query([
                    'code' => $code,
                    'message' => $message
                ]));
                // return response()->json(['success' => false, 'message' => 'Thanh toán thất bại']);
            }
        } catch (\Exception $e) {
            logger('Log bug callback vnpay', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            return redirect()->away(env('FRONTEND_URL') . "/payment-result?status=failed");
            // return response()->json(['success' => false, 'message' => 'Thanh toán thất bại']);
        }
    }

    public function processRefundCancelOrderPayment(Order $order, $request)
    {
        try {
            $user = auth('api')->user();
            $params = [
                'transaction_type' => '02',
                'txn_ref' => $order->unique_id,
                'transaction_no' => $order->transaction_id,
                'amount' => $order->total_price,
                'order_info' => "Hoàn tiền đơn hàng #" . $order->unique_id,
                'create_by' => $user->name,
                'transaction_date' => Carbon::parse($order->payment_created_at)->format('YmdHis')
            ];

            $response = Common::refundVnPayTransaction($params);
            // logger('Log bug refund payment', [
            //         'response' => $response
            // ]);
            // check sinature từ vnpay trả về
            // logger('valid signature', [$this->validateSignatureFromJson($response)]);
            if (!Common::validateSignatureFromJson($response)) {
                throw new ApiException('Có lỗi xảy ra, vui lòng liên hệ administrator!!');
            }

            if ($response['vnp_ResponseCode'] == "00") {
                $order->update([
                    'payment_status' => 'refunded',
                    'payment_date' => now(),
                    'transaction_id' => $response['vnp_TransactionNo'],
                    'reason'  => $request->reason,
                ]);
            }

            // Hoàn lại số lượng sản phẩm
            Common::restoreOrderStock($order);

            // Hoàn lại voucher
            Common::revertVoucherUsageInline($order);

            Common::sendOrderStatusMail($order, 'cancelled');

            return ApiResponse::success('Hủy đơn hàng thành công!', data: [
                'order_id'       => $order->unique_id,
                'status'         => $order->status,
            ]);
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
}
