<?php

namespace App\Jobs;

use App\Mail\ReturnApprovedMail;
use App\Models\Order;
use App\Services\SystemSettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendReturnApprovedMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $orderId;

    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle(SystemSettingService $settings): void
    {
        $order = Order::query()->find($this->orderId);
        if (!$order) {
            return;
        }

        // Chỉ gửi khi thật sự đang ở trạng thái approved
        if ($order->return_status !== 'approved') {
            return;
        }

        if (empty($order->shipping_email)) {
            return;
        }

        try {
            // 1) Lấy config mới nhất từ DB (+ fallback .env) và áp dụng runtime
            $mail = $settings->getEmailConfig(true); // decrypt password
            $settings->applyMailConfig($mail);

            Mail::to($order->shipping_email, $order->shipping_name ?? null)
                ->send(new ReturnApprovedMail($order->withoutRelations()));
        } catch (\Throwable $e) {
            Log::error('SendReturnApprovedMailJob failed', [
                'order_id' => $this->orderId,
                'error'    => $e->getMessage(),
            ]);
            throw $e; // để queue retry
        }
    }
}