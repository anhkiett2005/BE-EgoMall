<?php

namespace App\Jobs;

use App\Actions\ZaloPay\QueryRefundAction;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class QueryZaloPayRefundJob implements ShouldQueue
{
     use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

     protected $orderId;
     protected $params;
     protected $retryCount;

    /**
     * Create a new job instance.
     */
    public function __construct($orderId, $params, $retryCount = 0)
    {
        $this->orderId = $orderId;
        $this->params = $params;
        $this->retryCount = $retryCount;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Lấy order
        $order = Order::find($this->orderId);

        if(!$order) {
            logger('Order not found', ['order_id' => $this->orderId]);
            return;
        }

        // Gọi API query refund từ ZaloPay
        $result = QueryRefundAction::run($this->params);

        // logger('Data result', [
        //     'response' => $result
        // ]);

        if ($result['return_code'] == 1) {
            $order->update([
                'payment_status' => 'refunded',
                'payment_date' => now(),
            ]);
        }else if ($result['return_code'] == 3 && $this->retryCount < 3) {
            $delays = [5, 10, 20];
            $delay = $delays[$this->retryCount] ?? 20;

            self::dispatch(
                $this->orderId,
                $this->params,
                $this->retryCount + 1
            )->delay(now()->addSeconds($delay));
        }else if ($result['return_code'] == 2) {
            logger('Query refund failed', [
                'response' => $result
            ]);

            $order->update([
                'payment_status' => 'refund_failed',
                'payment_date' => now(),
            ]);
        }
    }
}
