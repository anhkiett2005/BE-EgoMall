<?php

namespace App\Jobs;

use App\Mail\OrderStatusMail;
use App\Models\Order;
use App\Services\SystemSettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendOrderStatusMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Order $order;
    protected string $status;

    public function __construct(Order $order, string $status)
    {
        $this->order = $order;
        $this->status = $status;
    }

    public function handle(SystemSettingService $settings): void
    {
        $email = $this->order->shipping_email;
        if (empty($email)) {
            Log::warning("Không có email để gửi trạng thái đơn hàng (ID: {$this->order->id})");
            return;
        }

        try {
            // 1) Lấy config mới nhất từ DB (+ fallback .env) và áp dụng runtime
            $mail = $settings->getEmailConfig(true); // decrypt password
            $settings->applyMailConfig($mail);

            // 2) Gửi mail
            Mail::to($email)->send(new OrderStatusMail($this->order, $this->status));

            Log::info("Đã gửi mail trạng thái [{$this->status}] cho đơn hàng ID: {$this->order->id}");
        } catch (\Throwable $e) {
            Log::error("Gửi mail đơn hàng lỗi (ID: {$this->order->id}): {$e->getMessage()}");
            throw $e; // cho phép retry nếu cần
        }
    }
}