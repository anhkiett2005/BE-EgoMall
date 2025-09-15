<?php

namespace App\Http\Controllers\Api\Partner\SePay\Event\MoneyIn;

use App\Classes\Common;
use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\Order;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EventMoneyIn extends Controller
{
    public function eventMoneyIn(Request $request)
    {
        $apiKeyHeader = $request->header('Authorization');

        $apiKeyFromHeader = str_replace('Apikey ', '', $apiKeyHeader);

        $apiKey = env('SEPAY_API_KEY');

        if($apiKeyFromHeader !== $apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid api key',
            ],Response::HTTP_UNAUTHORIZED);
        }

        if(empty($request->all())) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data or empty data',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }else {

            $financialTransactions = new FinancialTransaction();

            $dataSepay = [
            'id' => $request->id ?? null,
            'gateway' => $request->gateway ?? null,
            'transactionDate' => $request->transactionDate ?? null,
            'accountNumber' => $request->accountNumber ?? null,
            'code' => $request->code ?? null,
            'content' => $request->content ?? null,
            'transferType' => $request->transferType ?? null,
            'transferAmount' => $request->transferAmount ?? null,
            'accumulated' => $request->accumulated ?? null,
            'subAccount' => $request->subAccount ?? null,
            'referenceCode' => $request->referenceCode ?? null,
            'description' => $request->description ?? null
            ];

            if($request->content !== null) {
                if(preg_match('/#(\w+)/', $request->content, $matches)) {
                    $uniqueId = $matches[1];
                    $financialTransactions->order_id = $uniqueId;
                }
            }


        }

        $financialTransactions->sepay_data = $dataSepay;
        $financialTransactions->amount = $request->transferAmount;
        $financialTransactions->save();

        $order = Order::where('unique_id', $uniqueId)->first();

        if(!is_null($order)) {
            $order->update([
                'payment_status' => 'paid',
                'payment_date'   => now(),
                'transaction_id' => $request->id,
            ]);

            Common::sendOrderStatusMail($order, 'ordered');

            return response()->json([
                'success' => true,
                'message' => 'Order payment successful'
            ], Response::HTTP_OK);
        }else {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], Response::HTTP_NOT_FOUND);
        }




    }
}
