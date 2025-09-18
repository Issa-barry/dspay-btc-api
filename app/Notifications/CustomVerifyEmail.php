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
        // lien signé Laravel (backend)
        $backendUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
        );

        // lien front pour ton app (comme sur ta capture)
        $frontend = rtrim(config('app.frontend_url', env('FRONTEND_URL', '')), '/');
        if ($frontend) {
            return $frontend . '/auth/validation?redirect=' . urlencode($backendUrl);
        }

        // fallback : lien backend direct
        return $backendUrl;
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
