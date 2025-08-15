<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SetPasswordMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $roleName) {}

    public function build(): self
    {
        $frontendResetPasswordUrl = rtrim(config('app.frontend_url'), '/') . '/auth/forgot-password';

        return $this
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Thiết lập mật khẩu cho tài khoản EgoMall')
            ->view('emails.set-password')
            ->with([
                'userName'          => $this->user->name,
                'userEmail'         => $this->user->email,
                'roleName'          => $this->roleName,
                'resetPasswordLink' => $frontendResetPasswordUrl,
            ]);
    }
}
