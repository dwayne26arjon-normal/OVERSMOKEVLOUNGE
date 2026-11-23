<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminPasswordResetCode extends Notification
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
            ->subject('Your Oversmoke Vlounge admin password reset code')
            ->view([
                'html' => 'emails.password-reset-code',
                'text' => 'emails.text.password-reset-code',
            ], [
                'accountType' => 'owner or staff',
                'code' => $this->code,
            ]);
    }
}
