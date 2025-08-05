<?php

namespace App\Mail;

use App\Models\Promotion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PromotionNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Promotion $promotion) {}

    public function build(): self
    {
        return $this
            ->subject('[EgoMall] Khuyến mãi mới: ' . $this->promotion->name)
            ->view('emails.promotion_notification')
            ->with([
                'promotion' => $this->promotion,
            ]);
    }
}
