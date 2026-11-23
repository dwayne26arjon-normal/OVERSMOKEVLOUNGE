<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerPasswordResetCode extends Notification
{
    use Queueable;

    public function __construct(public readonly string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Oversmoke Vlounge password reset code')
            ->view([
                'html' => 'emails.password-reset-code',
                'text' => 'emails.text.password-reset-code',
            ], [
                'accountType' => 'customer',
                'code' => $this->code,
            ]);
    }
}
