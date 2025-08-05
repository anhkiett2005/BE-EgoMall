<?php

namespace App\Jobs;

use App\Mail\PromotionNotificationMail;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPromotionMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Promotion $promotion
    ) {}

    public function handle(): void
    {
        Mail::to($this->user->email)->send(
            new PromotionNotificationMail($this->promotion)
        );
    }
}
