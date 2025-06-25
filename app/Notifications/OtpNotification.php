<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

class OtpNotification extends Notification implements ShouldQueue
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
        ->subject('Mã Xác Thực OTP - EgoMall')
        ->view(
            'emails.opt',
            [
                'otp' => $this->otp,
                'expiresInMinutes' => $this->ttl,
                'notifiable' => $notifiable,
            ]
        );
    }
}
