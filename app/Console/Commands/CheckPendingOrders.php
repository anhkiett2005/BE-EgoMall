<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckPendingOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:pending-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the orders that are not yet paid';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $orders = Order::where('status', 'ordered')
                            ->where('payment_method', '!=', 'COD')
                            ->where('payment_status', 'unpaid')
                            ->where('payment_created_at', '<=', now()->subHours(24))
                            ->get();

            foreach ($orders as $order) {
                // // Xóa order details trước để tránh lỗi khóa ngoại
                // OrderDetail::where('order_id', $order->id)->delete();

                // Xóa order
                $order->delete();

                // $this->info("Đã xóa đơn hàng #{$order->id}");
            }

            Log::channel('check_pending_order')->info('Đã xóa đơn hàng #' . implode(',', $orders->pluck('id')->toArray()));

            // $this->info('Check pending orders and delete successfully');

        }catch (\Exception $e) {
            // logger()->error('Error check pending orders', [
            //             'order_id' => $orders->pluck('id')->toArray(),
            //             'error_message' => $e->getMessage(),
            //             'stack_trace' => $e->getTraceAsString(),
            //             'error_file' => $e->getFile(),
            // ]);
            // $this->error('Error check pending orders');
            Log::channel('check_pending_order')->error('Error check pending orders', [
                        'order_id' => $orders->pluck('id')->toArray(),
                        'error_message' => $e->getMessage(),
                        'stack_trace' => $e->getTraceAsString(),
                        'error_line' => $e->getLine(),
                        'error_file' => $e->getFile(),
            ]);
        }
    }
}
