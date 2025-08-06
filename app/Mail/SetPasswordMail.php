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

    public User $user;
    public string $roleName;

    public function __construct(User $user, string $roleName)
    {
        $this->user = $user;
        $this->roleName = $roleName;
    }

    public function build()
    {
        $frontendResetPasswordUrl = config('app.frontend_url') . '/auth/forgot-password';

        return $this->subject('Thiết lập mật khẩu cho tài khoản EgoMall')
            ->view('emails.set-password')
            ->with([
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'roleName' => $this->roleName,
                'resetPasswordLink' => $frontendResetPasswordUrl,
            ]);
    }
}
