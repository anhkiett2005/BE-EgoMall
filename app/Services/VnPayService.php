<?php
namespace App\Services;

class VnPayService {
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

    /**
     * Create payment link VnPay API with order data
     */
    public function createPaymentLink($order)
    {
        try {
            $startTime = date("YmdHis");
            $expire = date('YmdHis', strtotime('+15 minutes', strtotime($startTime)));


            $vnp_TxnRef = $order->unique_id;
            $vnp_Amount = $order->total_price;
            $vnp_Locale = 'vn';
            // $vnp_BankCode = "NCB";
            $vnp_BankCode = "";
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

            return $vnp_Url;
        } catch (\Exception $e) {
            // quăng lỗi lên cho controller bắt
            throw $e;
        }
    }

    /**
     * Getter method
     */
    public function getConfig($key)
    {
        return $this->{$key} ?? null;
    }
}
