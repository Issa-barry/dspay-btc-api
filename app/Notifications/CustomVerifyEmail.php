<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class CustomVerifyEmail extends BaseVerifyEmail
{
    public $verificationCode;

    public function __construct()
    {
        // Le code sera défini avant l'envoi
    }

    public function toMail($notifiable)
    {
        // Générer le code de vérification si pas déjà fait
        if (!$this->verificationCode) {
            $this->verificationCode = $notifiable->generateVerificationCode();
        }

        return (new MailMessage)
            ->subject('Code de vérification - ' . config('app.name'))
            ->view('emails.verify', [
                'code'     => $this->verificationCode,
                'appName'  => config('app.name'),
                'userName' => $notifiable->nom ?? $notifiable->prenom ?? $notifiable->email,
                'expiresIn' => 15, // minutes
            ]);
    }
}
