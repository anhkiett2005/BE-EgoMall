<?php

namespace App\Http\Controllers\Api\Front;

use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Jobs\SendOrderStatusMailJob;
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
    protected $vnp_Returnurl;

    protected $vnp_apiUrl;
    protected $apiUrl;

    public function __construct()
    {
        $this->vnp_TmnCode = env('VNP_TMN_CODE'); // Mã định danh merchant
        $this->vnp_HashSecret = env('VNP_HASH_SECRECT_KEY'); // Secret key (Sửa lỗi sai: SECRECT -> SECRET)
        $this->vnp_Url = env('VNP_URL');
        $this->vnp_Returnurl = env('VNP_RETURN_URL'); // Đổi sang route để dễ quản lý
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
                "vnp_ReturnUrl" => $this->vnp_Returnurl,
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

            return ApiResponse::success(data: [
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

    public function paymentSuccess(Request $request)
    {
        try {
            if (!$this->validateSignature($request->all())) {
                // return redirect()->away(env('FRONTEND_URL') . '/payment-result?status=failed');
                return ApiResponse::error('Xác thực thất bại', Response::HTTP_BAD_REQUEST);
            }

            //  // Lấy đơn hàng
            $order = Order::where('unique_id', $request->vnp_TxnRef)->first();
            if (!$order) {
                return redirect()->away(env('FRONTEND_URL') . '/payment-result?status=failed');
                // return response()->json(['success' => false, 'message' => 'Không tìm thấy đơn hàng'], 400);
            }

            if ($request->vnp_ResponseCode == "00") { // Thành công
                // Chặn nếu đơn đã hủy
                if ($order->status === 'cancelled') {
                    logger('VNPAY callback ignored - order cancelled', ['order_id' => $order->unique_id]);
                    return redirect()->away(env('FRONTEND_URL') . "/payment-result?status=ignored");
                }

                $order->update([
                    'payment_status' => 'paid',
                    'payment_date'   => now(),
                    'transaction_id' => $request->vnp_TransactionNo,
                ]);

                Common::sendOrderStatusMail($order, 'ordered');

                // gửi email cảm ơn
                // Mail::to($order->user->email)->queue(new OrderSuccessMail($order,'success'));


                return redirect()->away(env('FRONTEND_URL') . "/payment-result?status=success&order_id=" . $order->unique_id);
                // return response()->json(['success' => true, 'message' => 'Thanh toán thành công',]);

            } else {
                return redirect()->away(env('FRONTEND_URL') . "/payment-result?status=failed");
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

    public function processRefundPayment(Order $order)
    {
        try {
            $user = auth('api')->user();
            $params = [
                'transaction_type' => '02',
                'txn_ref' => $order->unique_id,
                'transaction_no' => $order->transaction_id,
                'amount' => $order->total_price,
                'order_info' => "Hoàn tiền đơn hàng: " . $order->unique_id,
                'create_by' => $user->name,
                'transaction_date' => Carbon::parse($order->payment_created_at)->format('YmdHis')
            ];

            $response = Common::refundVnPayTransaction($params);
            // logger('Log bug refund payment', [
            //         'response' => $response
            // ]);
            // check sinature từ vnpay trả về
            // logger('valid signature', [$this->validateSignatureFromJson($response)]);
            if (!$this->validateSignatureFromJson($response)) {
                throw new ApiException('Có lỗi xảy ra, vui lòng liên hệ administrator!!');
            }

            if ($response['vnp_ResponseCode'] == "00") {
                $order->update([
                    'payment_status' => 'refunded',
                    'payment_date' => now(),
                    'transaction_id' => $response['vnp_TransactionNo']
                ]);
            }

            return ApiResponse::success('Hủy đơn hàng thành công!!');
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

    private function validateSignature($requestData)
    {
        $vnp_SecureHash = $requestData['vnp_SecureHash'];
        $inputData = array();
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }

        unset($inputData['vnp_SecureHash']);
        ksort($inputData);
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, $this->vnp_HashSecret);

        return $secureHash === $vnp_SecureHash;
    }

    public function validateSignatureFromJson(array $response)
    {
        $vnp_HashSecret = $this->vnp_HashSecret;
        $vnp_SecureHash = $response['vnp_SecureHash'] ?? '';

        // Đảm bảo thứ tự đúng như VNPAY yêu cầu
        $fields = [
            'vnp_ResponseId',
            'vnp_Command',
            'vnp_ResponseCode',
            'vnp_Message',
            'vnp_TmnCode',
            'vnp_TxnRef',
            'vnp_Amount',
            'vnp_BankCode',
            'vnp_PayDate',
            'vnp_TransactionNo',
            'vnp_TransactionType',
            'vnp_TransactionStatus',
            'vnp_OrderInfo'
        ];

        $data = [];
        foreach ($fields as $field) {
            $data[] = $response[$field] ?? '';
        }

        $hashData = implode('|', $data);
        // logger('hashData', [$hashData]);
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        return $secureHash === $vnp_SecureHash;
    }
}
