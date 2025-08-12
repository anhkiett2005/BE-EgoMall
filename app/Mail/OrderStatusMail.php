<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    public string $status;

    public function __construct(Order $order, string $status)
    {
        $this->order = $order;
        $this->status = $status;
    }

    public function build(): self
    {
        $subjectMap = [
            'ordered'   => 'Đơn hàng của bạn đã được đặt thành công!',
            'confirmed' => 'Đơn hàng của bạn đã được xác nhận!',
            'delivered' => 'Đơn hàng đã giao thành công!',
        ];

        return $this
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject($subjectMap[$this->status] ?? 'Cập nhật đơn hàng')
            ->view('emails.order-status')
            ->with([
                'order'  => $this->order,
                'status' => $this->status,
            ]);
    }
}
