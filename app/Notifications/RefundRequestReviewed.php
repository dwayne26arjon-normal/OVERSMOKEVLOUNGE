<?php

namespace App\Notifications;

use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RefundRequestReviewed extends Notification
{
    use Queueable;

    public function __construct(private RefundRequest $refundRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->refundRequest->loadMissing('order');
        $message = $this->refundRequest->status === 'approved'
            ? 'Your refund request was approved.'
            : 'Your refund request was declined.';

        return [
            'refund_request_id' => $this->refundRequest->id,
            'order_id' => $this->refundRequest->order_id,
            'order_number' => $this->refundRequest->order->order_number,
            'message' => $message,
        ];
    }
}
