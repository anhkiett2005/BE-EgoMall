<?php

namespace App\Notifications;

use App\Services\SystemSettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

class OtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public function backoff(): int
    {
        return 10;
    } // giây

    protected string $otp;
    protected int $ttl;

    public function __construct(string $otp, int $ttlMinutes)
    {
        $this->otp = $otp;
        $this->ttl = $ttlMinutes;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        // Áp cấu hình mail mới nhất để queue không dùng config cũ
        $settings = app(SystemSettingService::class);
        $mail = $settings->getEmailConfig(true);
        $settings->applyMailConfig($mail);

        return (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Mã Xác Thực OTP - EgoMall')
            ->view('emails.otp', [
                'otp' => $this->otp,
                'expiresInMinutes' => $this->ttl,
                'notifiable' => $notifiable,
            ]);
    }
}