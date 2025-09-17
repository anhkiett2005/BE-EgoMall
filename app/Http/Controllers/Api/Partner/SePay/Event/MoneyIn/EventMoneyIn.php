<?php

namespace App\Http\Controllers\Api\Partner\SePay\Event\MoneyIn;

use App\Classes\Common;
use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\Order;
use App\Response\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EventMoneyIn extends Controller
{
    public function eventMoneyIn(Request $request)
    {
            // 1. Validate API Key
            $apiKeyHeader = $request->header('Authorization');
            $apiKeyFromHeader = str_replace('Apikey ', '', $apiKeyHeader);
            $apiKey = env('SEPAY_API_KEY');

            if ($apiKeyFromHeader !== $apiKey) {
                return ApiResponse::error('Invalid API key', Response::HTTP_UNAUTHORIZED);
            }

            // 2. Validate data
            if (empty($request->all())) {
                return ApiResponse::error('Invalid or empty data', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // 3. Check duplicate transaction (chống retry trùng lặp)
            $existingTx = FinancialTransaction::where('sepay_data->id', $request->id)->first();
            if ($existingTx) {
                return ApiResponse::success('Transaction already processed');
            }

            // 4. Parse SePay data
            $dataSepay = [
                'id' => $request->id,
                'gateway' => $request->gateway,
                'transactionDate' => $request->transactionDate,
                'accountNumber' => $request->accountNumber,
                'code' => $request->code,
                'content' => $request->content,
                'transferType' => $request->transferType,
                'transferAmount' => $request->transferAmount,
                'accumulated' => $request->accumulated,
                'subAccount' => $request->subAccount,
                'referenceCode' => $request->referenceCode,
                'description' => $request->description
            ];

            // 5. Extract order_id từ content
            $uniqueId = null;
            if ($request->content && preg_match('/don hang\s+(\w+)/i', $request->content, $matches)) {
                $uniqueId = $matches[1];

                // Thêm dấu "-" sau "ORD"
                if (str_starts_with($uniqueId, 'ORD') && !str_contains($uniqueId, '-')) {
                    $uniqueId = substr($uniqueId, 0, 3) . '-' . substr($uniqueId, 3);
                }
            }

            $order = Order::where('unique_id', $uniqueId)->first();

            // 6. Update order nếu tồn tại
            if ($uniqueId && !is_null($order)) {
                $order->update([
                    'payment_status' => 'paid',
                    'payment_date'   => now(),
                    'transaction_id' => $request->id,
                ]);

                Common::sendOrderStatusMail($order, 'ordered');

            }

            // 8. Save transaction
            $financialTransaction = new FinancialTransaction();
            $financialTransaction->order_id = $order->id;
            $financialTransaction->amount = $request->transferAmount;
            $financialTransaction->sepay_data = $dataSepay;
            $financialTransaction->save();



            // 9. Always return success để thông báo cho SePay
            return ApiResponse::success('Webhook processed successfully');
    }
}

