<?php

namespace App\Http\Controllers\Api\Partner\VnPay\Event\MoneyIn;

use App\Classes\Common;
use App\Enums\VnPayStatus;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\Order;
use App\Services\VnPayService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EventMoneyIn extends Controller
{

    protected $vnPayService;

    public function __construct(VnPayService $vnPayService)
    {
        $this->vnPayService = $vnPayService;
    }

    public function eventMoneyIn(Request $request)
    {
        try {

            // logger([
            //     'request' => $request->all()
            // ]);

            $requestData = $request->all();

            if (!Common::validateSignature($requestData, $this->vnPayService->getConfig('vnp_HashSecret'))) {
                    return response()->json([
                        'RspCode' => VnPayStatus::CODE_97->value,
                        'Message' => VnPayStatus::description(VnPayStatus::CODE_97->value)
                    ],Response::HTTP_BAD_REQUEST);
            }

            // Check duplicate transaction (chống retry trùng lặp)


            $existingTx = FinancialTransaction::where('vnpay_data->vnp_TransactionNo', $request->vnp_TransactionNo)->first();
            if ($existingTx) {
                return response()->json([
                    'RspCode' => VnPayStatus::CODE_02->value,
                    'Message' => VnPayStatus::description(VnPayStatus::CODE_02->value)
                ]);
            }


            // lưu dữ liệu VnPay vào database
            $dataVnPay = [
                'vnp_Amount' => $request->vnp_Amount,
                'vnp_BankCode' => $request->vnp_BankCode,
                'vnp_BankTranNo' => $request->vnp_BankTranNo,
                'vnp_CardType' => $request->vnp_CardType,
                'vnp_OrderInfo' => $request->vnp_OrderInfo,
                'vnp_PayDate' => $request->vnp_PayDate,
                'vnp_ResponseCode' => $request->vnp_ResponseCode,
                'vnp_TmnCode' => $request->vnp_TmnCode,
                'vnp_TransactionNo' => $request->vnp_TransactionNo,
                'vnp_TransactionStatus' => $request->vnp_TransactionStatus,
                'vnp_TxnRef' => $request->vnp_TxnRef,
                'vnp_SecureHash' => $request->vnp_SecureHash
            ];

            // Lấy đơn hàng
            $order = Order::where('unique_id', $request->vnp_TxnRef)->first();

            if($request->vnp_ResponseCode == "00") {
                if(!is_null($order)) {

                    // check amount từ VnPay trả về
                    $amount = (int) ($request->vnp_Amount / 100);

                    if($order->total_price == $amount) {
                        $order->update([
                            'payment_status' => 'paid',
                            'payment_date'   => now(),
                            'transaction_id' => $request->vnp_TransactionNo,
                        ]);

                        // Lưu lịch sử giao dịch VnPay
                        $financialTransaction = new FinancialTransaction();
                        $financialTransaction->order_id = $order->id;
                        $financialTransaction->amount = $amount;
                        $financialTransaction->vnpay_data = $dataVnPay;
                        $financialTransaction->save();

                        Common::sendOrderStatusMail($order, 'ordered');

                        return response()->json([
                            'RspCode' => VnPayStatus::SUCCESS->value,
                            'Message' => VnPayStatus::description(VnPayStatus::SUCCESS->value)
                        ]);
                    }else {
                        return response()->json([
                            'RspCode' => VnPayStatus::CODE_04->value,
                            'Message' => VnPayStatus::description(VnPayStatus::CODE_04->value)
                        ],Response::HTTP_BAD_REQUEST);
                    }
                }
            }
        } catch (\Exception $e) {
            logger('Log bug', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'RspCode' => VnPayStatus::CODE_99->value,
                'Message' => VnPayStatus::description(VnPayStatus::CODE_99->value)
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
