<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Les policies de l'application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Enregistre les services d'auth/policies.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // URL front pour le reset (ex: https://app.dspay.com/reset-password)
        $frontReset = config('app.frontend_newpassword_url')
            ?? config('app.frontend_url')
            ?? (config('app.url') . '/reset-password');

        // 1) Construit l'URL utilisée par l'écosystème Password::sendResetLink(...)
        ResetPassword::createUrlUsing(function ($user, string $token) use ($frontReset) {
            $base = rtrim($frontReset, '/');
            $glue = str_contains($base, '?') ? '&' : '?';
            return "{$base}{$glue}token={$token}&email=" . urlencode($user->email);
        });

        // 2) Utilise TON template d'email
        ResetPassword::toMailUsing(function ($user, string $token) use ($frontReset) {
            $base = rtrim($frontReset, '/');
            $glue = str_contains($base, '?') ? '&' : '?';
            $url  = "{$base}{$glue}token={$token}&email=" . urlencode($user->email);

            return (new MailMessage)
                ->subject('Réinitialisation de votre mot de passe')
                ->view('emails.passwordReset', [
                    'appName'       => config('app.name'),
                    'url'           => $url,
                    'user'          => $user,
                    'userFirstName' => $user->prenom ?? $user->first_name ?? null,
                    'userLastName'  => $user->nom ?? $user->last_name ?? null,
                    'userName'      => $user->name ?? null,
                    'expiresIn'     => config('auth.passwords.' . config('auth.defaults.passwords') . '.expire') . ' minutes',
                ]);
        });

        // ⚠️ Ne pas utiliser URL::defaults(['expire' => ...]) ici :
        // cela ajouterait 'expire' à TOUTES les URLs. L'expiration du lien de vérification
        // se règle via la notification VerifyEmail ou la config, pas ici.
    }
}
 