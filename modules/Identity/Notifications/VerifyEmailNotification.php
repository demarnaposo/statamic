<?php

namespace Modules\Identity\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Modules\Identity\Domain\Customer;

final class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const EXPIRE_MINUTES = 60;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(Customer $notifiable): MailMessage
    {
        return ActionMail::make(
            subject: 'Verifikasi alamat email Anda',
            name: $notifiable->name,
            intro: ['Terima kasih telah membuat akun. Klik tombol di bawah untuk memverifikasi alamat email Anda.'],
            actionText: 'Verifikasi email',
            actionUrl: self::url($notifiable),
            outro: [
                'Tautan ini berlaku selama '.self::EXPIRE_MINUTES.' menit.',
                'Jika Anda tidak merasa membuat akun, abaikan email ini.',
            ],
        );
    }

    public static function url(Customer $customer): string
    {
        return URL::temporarySignedRoute('portal.verification.verify', Carbon::now()->addMinutes(self::EXPIRE_MINUTES), [
            'id' => $customer->getKey(),
            'hash' => sha1($customer->getEmailForVerification()),
        ]);
    }
}
