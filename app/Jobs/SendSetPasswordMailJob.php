<?php

namespace App\Jobs;

use App\Mail\SetPasswordMail;
use App\Models\User;
use App\Services\SystemSettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendSetPasswordMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected User $user,
        protected string $roleName
    ) {}

    // (tuỳ chọn) retry/backoff
    public int $tries = 3;
    public function backoff(): int
    {
        return 10;
    }

    public function handle(SystemSettingService $settings): void
    {
        if (empty($this->user->email)) {
            Log::warning("SetPasswordMail: user {$this->user->id} không có email.");
            return;
        }

        try {
            // 1) Áp cấu hình mail runtime mới nhất
            $mail = $settings->getEmailConfig(true);
            $settings->applyMailConfig($mail);

            // 2) Gửi
            Mail::to($this->user->email)
                ->send(new SetPasswordMail($this->user, $this->roleName));

            Log::info("Đã gửi mail set password cho user {$this->user->id}");
        } catch (\Throwable $e) {
            Log::error("Gửi mail set password lỗi (user {$this->user->id}): {$e->getMessage()}");
            throw $e; // cho phép queue retry
        }
    }
}