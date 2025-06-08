<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OtpNotification extends Notification
{
    use Queueable;

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
        return (new MailMessage)
            ->subject('Mã Xác Thực OTP của bạn')
            ->greeting("Chào {$notifiable->name},")
            ->line("Mã xác thực của bạn là **{$this->otp}**.")
            ->line("Mã này có hiệu lực trong {$this->ttl} phút.")
            ->salutation('Trân trọng, Egomall Shop');
    }
}
