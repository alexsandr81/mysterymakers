<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/** Письмо со ссылкой для сброса пароля (на языке сайта) */
class ResetPasswordNotification extends Notification
{
    public function __construct(private string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
        $minutes = (int) config('auth.passwords.users.expire', 60);

        // Пока письма пишутся в лог (MAIL_MAILER=log), в нём ссылка искажена кодировкой —
        // поэтому дублируем её в лог обычным текстом, чтобы можно было скопировать.
        if (config('mail.default') === 'log') {
            Log::info('PASSWORD RESET LINK for ' . $notifiable->email . ': ' . $url);
        }

        return (new MailMessage)
            ->subject(__('shop.reset_mail_subject'))
            ->view('emails.password-reset', ['url' => $url, 'minutes' => $minutes]);
    }
}
