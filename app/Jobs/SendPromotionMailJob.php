<?php

namespace App\Jobs;

use App\Mail\PromotionNotificationMail;
use App\Models\Promotion;
use App\Models\User;
use App\Services\SystemSettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendPromotionMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public User $user, public Promotion $promotion) {}

    // (khuyến nghị) backoff & tries
    public int $tries = 3;
    public function backoff(): int { return 10; } // giây

    public function handle(SystemSettingService $settings): void
    {
        if (empty($this->user->email)) {
            Log::warning("PromotionMail: user {$this->user->id} không có email.");
            return;
        }

        // 1) Áp config mail mới nhất
        $mail = $settings->getEmailConfig(true);
        $settings->applyMailConfig($mail);

        // 2) Gửi
        Mail::to($this->user->email)->send(
            new PromotionNotificationMail($this->promotion)
        );

        Log::info("PromotionMail đã gửi cho user {$this->user->id} (promotion {$this->promotion->id}).");
    }
}
