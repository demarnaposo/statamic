<?php

namespace Modules\Identity\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/** Email transaksional berbahasa Indonesia dengan satu tombol aksi (template milik modul). */
final class ActionMail
{
    /** @param  list<string>  $intro  @param  list<string>  $outro */
    public static function make(string $subject, string $name, array $intro, string $actionText, string $actionUrl, array $outro): MailMessage
    {
        return (new MailMessage)
            ->subject($subject)
            ->view(['identity::mail.action', 'identity::mail.action-text'], [
                'subject' => $subject,
                'name' => $name,
                'intro' => $intro,
                'actionText' => $actionText,
                'actionUrl' => $actionUrl,
                'outro' => $outro,
                'appName' => config('mail.from.name', 'Sertifika'),
            ]);
    }
}
