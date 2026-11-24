<?php

namespace App\Notifications;

use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RefundRequestSubmitted extends Notification
{
    use Queueable;

    public function __construct(private RefundRequest $refundRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->refundRequest->loadMissing(['order', 'customer']);

        return [
            'refund_request_id' => $this->refundRequest->id,
            'order_id' => $this->refundRequest->order_id,
            'order_number' => $this->refundRequest->order->order_number,
            'buyer' => $this->refundRequest->customer->name,
            'message' => 'A refund request from '.$this->refundRequest->customer->name.' needs review.',
        ];
    }
}
