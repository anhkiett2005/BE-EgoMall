<?php

namespace App\Http\Controllers\Api\Partner\PayOs\Event\MoneyIn;

use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\Order;
use App\Response\ApiResponse;
use Illuminate\Http\Request;
use PayOS\PayOS;
use Symfony\Component\HttpFoundation\Response;

class EventMoneyIn extends Controller
{
    protected $payOs;

    public function __construct(PayOS $payOs)
    {
        $this->payOs = $payOs;
    }

    public function eventMoneyIn(Request $request)
    {
        try {
            $webhookBody = $request->all();

            // logger('webhookBody', $webhookBody);

            $data = $this->payOs->verifyPaymentWebhookData($webhookBody);

            // Check duplicate transaction (chống retry trùng lặp)
            $existingTx = FinancialTransaction::where('payos_data->orderCode', $data['orderCode'])->first();

            if ($existingTx) {
                return ApiResponse::success('Transaction already processed');
            }

            // Lấy orderCode để cập nhật trạng thái order hiện tại
            $orderCode = $data['orderCode'] ?? null;

            if(is_null($orderCode)) {
                throw new ApiException('Missing orderCode', Response::HTTP_BAD_REQUEST);
            }

            // parse xử lý orderCode
            $orderId = (int) substr($orderCode, 0, strlen($orderCode) - 6); // bỏ 6 ký tự cuối (His)

            // Cập nhật trạng thái order
            $order = Order::find($orderId);

            if(!is_null($order)) {
                $order->update([
                    'payment_status' => 'paid',
                    'payment_date'   => now(),
                    'transaction_id' => $data['reference'],
                ]);

                Common::sendOrderStatusMail($order, 'ordered');
            }

            // Lưu lịch sử giao dịch
            $financialTransaction = new FinancialTransaction();
            $financialTransaction->order_id = $order->id;
            $financialTransaction->amount = $data['amount'];
            $financialTransaction->payos_data = $data;
            $financialTransaction->save();

            // return success thông báo thanh cong payOs
            return ApiResponse::success('Webhook processed successfully');
        }catch (ApiException $e) {
            throw $e;
        }catch (\Exception $e) {
            throw new ApiException($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
}
