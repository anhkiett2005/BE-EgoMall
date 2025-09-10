<?php

namespace App\Http\Controllers\Api\Front;

use App\Actions\ZaloPay\QueryRefundAction;
use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Jobs\QueryZaloPayRefundJob;
use App\Models\Coupon;
use App\Models\Order;
use App\Response\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ZaloPayController extends Controller
{
    protected $zaloPayAppId;
    protected $zaloPayKey1;
    protected $zaloPayKey2;
    protected $zaloPayUrl;
    protected $zaloPayUrlSuccess;
    protected $zaloPayReturnUrl;

    public function __construct()
    {
        $this->zaloPayAppId = env('ZALO_PAY_APP_ID');
        $this->zaloPayKey1 = env('ZALO_PAY_KEY_1');
        $this->zaloPayKey2 = env('ZALO_PAY_KEY_2');
        $this->zaloPayUrl = env('ZALO_PAY_URL');
        $this->zaloPayUrlSuccess = env('ZALO_PAY_URL_SUCCESS');
        $this->zaloPayReturnUrl = env('ZALO_PAY_RETURN_URL');
    }

    public function processPayment(Order $order)
    {
        try {
            $config = [
                "app_id" => $this->zaloPayAppId,
                "key1" => $this->zaloPayKey1,
                "key2" => $this->zaloPayKey2,
                "endpoint" => $this->zaloPayUrl,
                "return_url" => $this->zaloPayReturnUrl
            ];

            // dd($config);

            $appTransId = Carbon::now(config('app.timezone'))->format('ymd') . '_' . $order->unique_id;
            $appTime = Carbon::now(config('app.timezone'))->getTimestampMs();

            $embed_data =  [
                "redirecturl" => $this->zaloPayUrlSuccess,
                "orderId" => $order->unique_id
            ];

            if(!is_null($order->coupon_id)) {
                $coupon = Coupon::find($order->coupon_id);

                $embed_data['promotioninfo'] = json_encode([
                    "campaigncode" => $coupon->code
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }



            $items = [];

            foreach ($order->details as $item) {
                $items[] = [
                    "itemid" => $item->id,
                    "itemname" => $item->productVariant->variant_name,
                    "itemprice" => $item->price ?? $item->sale_price,
                    "itemquantity" => $item->quantity,
                    "itemgift" => $item->is_gift
                ];
            }

            // Thêm phí ship
            if ($order->shipping_fee > 0) {
                $items[] = [
                    "itemid"       => $order->id,
                    "itemname"     => "Phí vận chuyển",
                    "itemprice"    => (int) $order->shipping_fee,
                    "itemquantity" => 1,
                ];
            }

            $itemJson = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $data = [
                "app_id" => (int) $config['app_id'],
                "app_user" => (string) 'user' . $order->user_id,
                "app_trans_id" => $appTransId,
                "app_time" => $appTime,
                "amount" => (int) $order->total_price,
                "item" => $itemJson,
                "description" => 'EgoMall - Thanh toán đơn hàng #' . $order->unique_id,
                "embed_data" => json_encode($embed_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                "bank_code" => 'zalopayapp',
                "callback_url" => $config['return_url'],
                "title" => 'EgoMall - Thanh toán đơn hàng #' . $order->unique_id,
                "phone" => $order->shipping_phone,
                "email" => $order->shipping_email,
                "address" => $order->shipping_address
            ];

            // create mac signature
            $mac = $data['app_id'] . "|" . $data['app_trans_id'] . "|" . $data['app_user']
                    . "|" . $data['amount'] . "|" . $data['app_time'] . "|" . $data['embed_data']
                    . "|" . $data['item'];
            // dd($mac);

            $data['mac'] = hash_hmac('sha256', $mac, $config['key1']);
            // dd($data);

            // Lưu lại thời gian tạo thanh toán
            $order->payment_created_at = Carbon::now();
            $order->save();

            // Gọi API tạo đơn của ZaloPay và trả về url cho fe
            $response = Http::asJson()->post($config['endpoint'], $data);

            $result = $response->json();

            if($result['return_code'] == 1) {
                return ApiResponse::success(data: [
                    'redirect_url' => $result['order_url']
                ]);
            }else if($result['return_code'] == 2) {
                logger('Log bug error failed api zalopay', [
                    'data' => $result
                ]);

                throw new ApiException('Có lỗi xảy ra, vui lòng liên hệ administrator!!', Response::HTTP_BAD_REQUEST);
            }

        } catch (ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            logger('Log bug zalopay transaction', [
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
            logger('callback from zalopay', [
                'data' => $request->all()
            ]);

            // Lấy data từ callback ZaloPay trả về
            $dataStr = $request->input('data');
            $reqMac  = $request->input('mac');

            // Tạo lại mac signature
            $mac = hash_hmac('sha256', $dataStr, $this->zaloPayKey2);

            // Kiểm tra mac
            if($mac != $reqMac) {
                return response()->json([
                    'return_code' => -1,
                    'return_message' => 'mac not equal'
                ]);
            }

            // Xử lý data
            $dataJson = json_decode($dataStr, true);

            if(!$dataJson) {
                return response()->json([
                    'return_code' => -1,
                    'return_message' => 'invalid data'
                ]);
            }

            // Xử lý cập nhật đơn hàng
            $embedData = json_decode($dataJson['embed_data'], true);
            $orderId   = $embedData['orderId'] ?? null;

            $order = Order::where('unique_id', $orderId)->first();

            if(!$order) {
                return response()->json([
                    'return_code' => -1,
                    'return_message' => 'order not found'
                ]);
            }

            if ($order->payment_status === 'paid') {
                return response()->json([
                    'return_code'    => 2,
                    'return_message' => 'order already processed'
                ]);
            }

            $order->update([
                'payment_status' => 'paid',
                'payment_date'   => now(),
                'transaction_id' => $dataJson['zp_trans_id'],
            ]);

            Common::sendOrderStatusMail($order, 'ordered');

            return response()->json([
                'return_code'    => 1,
                'return_message' => 'success'
            ]);

        } catch(\Exception $e) {
            logger('Log bug callback zalopay', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'return_code'    => 0,
                'return_message' => $e->getMessage()
            ]);
        }
    }

    public function processRefundCancelOrderPayment(Order $order, $request)
    {
        try {
            $params = [
                'zp_key1' => env('ZALO_PAY_KEY_1'),
                'm_refund_id' => Carbon::now(config('app.timezone'))->format('ymd') .  '_'  . env('ZALO_PAY_APP_ID') . '_' . Str::random(10),
                'app_id' => env('ZALO_PAY_APP_ID'),
                'zp_trans_id' => $order->transaction_id,
                'amount' => $order->total_price,
                'timestamp' => Carbon::now(config('app.timezone'))->getTimestampMs(),
                'description' => "Hoàn tiền đơn hàng #" . $order->unique_id,
            ];

            $response = Common::refundZaloPayTransaction($params);

            // logger('Log data refund', [
            //     'response' => $response
            // ]);

            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'refund_processing',
                'transaction_id' => $response['refund_id'],
                'reason'  => $request->reason,
            ]);

            if($response['return_code'] == 3) {
                // gọi API query refund của ZaloPay
                $arrQueryParams = Arr::only($params, ['app_id', 'm_refund_id', 'timestamp','zp_key1']);

                // gọi queue query refund
                QueryZaloPayRefundJob::dispatch($order->id,$arrQueryParams)->delay(now()->addSeconds(2));
            }

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
