<?php

namespace App\Jobs;

use App\Mail\SetPasswordMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendSetPasswordMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;
    protected string $roleName;

    public function __construct(User $user, string $roleName)
    {
        $this->user = $user;
        $this->roleName = $roleName;
    }

    public function handle(): void
    {
        try {
            Mail::to($this->user->email)->send(new SetPasswordMail($this->user, $this->roleName));
        } catch (\Throwable $e) {
            logger()->error("Gửi mail đặt mật khẩu thất bại (User ID: {$this->user->id}) - Lỗi: {$e->getMessage()}");
        }
    }
}
