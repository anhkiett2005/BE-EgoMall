<?php

namespace App\Http\Controllers\Api\Front;

use App\Classes\Common;
use App\Enums\VnPayStatus;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Response\ApiResponse;
use App\Services\VnPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;


class VnPayController extends Controller
{
    protected $vnPayService;

    public function __construct(VnPayService $vnPayService)
    {
        $this->vnPayService = $vnPayService;
    }

    public function processPayment(Order $order)
    {
        try {
            $paymentUrl = $this->vnPayService->createPaymentLink($order);

            // Lưu lại thời gian tạo thanh toán
            $order->payment_created_at = Carbon::now();
            $order->save();

            return ApiResponse::success('Link thanh toán được tạo thành công.',data: [
                'redirect_url' => $paymentUrl
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


            if (!Common::validateSignature($request->all(), $this->vnPayService->getConfig('vnp_HashSecret'))) {
                // return redirect()->away(env('FRONTEND_URL') . '/payment-result?status=failed');
                // return ApiResponse::error('Xác thực thất bại', Response::HTTP_BAD_REQUEST);
                return redirect()->away(env('FRONTEND_URL') . "/profile/orders?" . Arr::query([
                    'code' => $code,
                    'message' => $message
                ]));
            }

            if ($request->vnp_ResponseCode == "00") { // Thành công
                return redirect()->away(env('FRONTEND_URL') . "/thank-you?" . Arr::query([
                    'code' => $code,
                    'message' => $message,
                    'order_id' => $request->vnp_TxnRef
                ]));

            } else {
                return redirect()->away(env('FRONTEND_URL') . "/profile/orders?" . Arr::query([
                    'code' => $code,
                    'message' => $message
                ]));
                // return response()->json(['success' => false, 'message' => 'Thanh toán thất bại']);
            }
        } catch (\Exception $e) {
            logger('Log bug returnUrl vnpay', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            return redirect()->away(env('FRONTEND_URL') . "/profile/orders?message=failed");
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
