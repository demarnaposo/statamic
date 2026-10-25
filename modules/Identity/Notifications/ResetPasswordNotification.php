<?php

namespace Modules\Identity\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Identity\Domain\Customer;

final class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(Customer $notifiable): MailMessage
    {
        return ActionMail::make(
            subject: 'Atur ulang kata sandi',
            name: $notifiable->name,
            intro: ['Kami menerima permintaan untuk mengatur ulang kata sandi akun Anda.'],
            actionText: 'Atur ulang kata sandi',
            actionUrl: route('portal.password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]),
            outro: [
                'Tautan ini berlaku selama '.config('auth.passwords.customers.expire').' menit.',
                'Jika Anda tidak meminta pengaturan ulang, abaikan email ini; kata sandi Anda tidak berubah.',
            ],
        );
    }
}
