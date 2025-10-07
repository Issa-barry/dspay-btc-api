<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    use JsonResponseTrait;

    public function __invoke(Request $request)
    {
        // NB: si tu veux éviter l’énumération d’emails, enlève "exists:users,email"
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists'   => "Aucun utilisateur n'est enregistré avec cette adresse email.",
            'email.required' => "L'adresse email est obligatoire.",
            'email.email'    => "Le format de l'adresse email est invalide.",
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return $this->responseJson(true, 'Lien de réinitialisation envoyé à votre email.');
        }

        if ($status === Password::RESET_THROTTLED) {
            // Délai d’attente (config/auth.php → passwords.users.throttle, par défaut 60s)
            $throttle = (int) config('auth.passwords.' . config('auth.defaults.passwords') . '.throttle', 60);

            return response()
                ->json([
                    'success' => false,
                    'message' => 'Un e-mail vient déjà d’être envoyé.',
                    'data' => [
                        // message affichable sous le champ email
                        'email' => ["Veuillez patienter encore {$throttle} seconde(s) avant de réessayer."],
                        'retry_after' => $throttle,
                    ],
                ], 429)
                ->header('Retry-After', $throttle);
        }

        if ($status === Password::INVALID_USER) {
            // Si tu enlèves la règle "exists", renvoie un message neutre pour ne pas divulguer
            return $this->responseJson(true, 'Si un compte existe avec cet email, un lien a été envoyé.');
        }

        // Par défaut: erreur fonctionnelle (pas 500)
        return $this->responseJson(false, 'Impossible d’envoyer le lien pour le moment.', [
            'email' => [__($status)]
        ], 422);
    }
} 
