<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class PasswordResetLinkController extends Controller
{
    use JsonResponseTrait;

    public function __invoke(Request $request)
    {
        // ✅ Validation manuelle (JSON, pas de page HTML)
        $v = Validator::make($request->all(), [
            'email' => ['required','email','exists:users,email'], // retire "exists" si tu veux éviter l'énumération
        ], [
            'email.required' => "L'adresse email est obligatoire.",
            'email.email'    => "Le format de l'adresse email est invalide.",
            'email.exists'   => "Aucun utilisateur n'est enregistré avec cette adresse email.",
        ]);

        if ($v->fails()) {
            return $this->responseJson(false, 'Échec de validation.', $v->errors(), 422);
        }

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return $this->responseJson(true, 'Lien de réinitialisation envoyé à votre email.');
        }

        // ⏳ Throttle (trop de demandes)
        if ($status === Password::RESET_THROTTLED) {
            $throttle = (int) config('auth.passwords.' . config('auth.defaults.passwords') . '.throttle', 60);

            return response()
                ->json([
                    'success' => false,
                    'message' => 'Un e-mail vient déjà d’être envoyé.',
                    'data'    => [
                        'email' => ["Veuillez patienter encore {$throttle} seconde(s) avant de réessayer."],
                        'retry_after' => $throttle,
                    ],
                ], 429)
                ->header('Retry-After', $throttle);
        }

        // 👤 Email inconnu (utile si tu enlèves la règle "exists")
        if ($status === Password::INVALID_USER) {
            // Variante privacy (200 neutre) :
            // return $this->responseJson(true, 'Si un compte existe avec cet email, un lien a été envoyé.');

            // Variante explicite (422 champ):
            return $this->responseJson(false, 'Adresse email introuvable.', [
                'email' => ["Aucun utilisateur n'est enregistré avec cette adresse email."],
            ], 422);
        }

        // 🧯 Fallback fonctionnel
        return $this->responseJson(false, 'Impossible d’envoyer le lien pour le moment.', [
            'email' => [__($status)],
        ], 422);
    }
}
