<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class CustomVerifyEmail extends BaseVerifyEmail
{
    protected function verificationUrl($notifiable)
    {
        // 1) Lien signé backend (utilise APP_URL)
        $backendUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id'   => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        // 2) URL front complète (déjà /auth/validation)
        $frontend = rtrim(config('app.frontend_verify_email_url', env('FRONTEND_VERIFY_EMAIL_URL', '')), '/');

        // 3) On renvoie /auth/validation?redirect=<signed>
        return $frontend . '?redirect=' . urlencode($backendUrl);
    }

    public function toMail($notifiable)
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Vérification de votre adresse e-mail')
            ->view('emails.verify', [
                'url'      => $url,
                'appName'  => config('app.name'),
                'userName' => $notifiable->nom ?? $notifiable->prenom ?? $notifiable->email,
            ]);
    }
}
