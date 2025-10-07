<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\JsonResponseTrait;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    use JsonResponseTrait;

    public function __invoke(Request $request)
    {
        // 1) Validation "API" (pas de $request->validate qui renvoie du HTML)
        $v = Validator::make($request->all(), [
            'token'                 => ['required'],
            'email'                 => ['required', 'email'],
            'password'              => ['required', 'string', 'min:8'],
            'password_confirmation' => ['required', 'same:password'], // <- séparé
        ], [
            'token.required'                 => 'Le lien de réinitialisation est invalide ou expiré.',
            'email.required'                 => "L'adresse email est obligatoire.",
            'email.email'                    => "L'adresse email n'est pas valide.",
            'password.required'              => 'Le mot de passe est obligatoire.',
            'password.min'                   => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password_confirmation.required' => 'La confirmation du mot de passe est obligatoire.',
            'password_confirmation.same'     => 'La confirmation du mot de passe ne correspond pas.',
        ]);

        if ($v->fails()) {
            return $this->responseJson(false, 'Échec de validation.', $v->errors(), 422);
        }

        // 2) Tentative de reset
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $verifiedAt = $user->getOriginal('email_verified_at');

                $user->forceFill([
                    'password'          => Hash::make($password),
                    'remember_token'    => Str::random(60),
                    'email_verified_at' => $verifiedAt,
                ])->saveQuietly();

                event(new PasswordReset($user));
            }
        );

        // 3) Mapping des statuts -> erreurs par champ + code HTTP
        if ($status !== Password::PASSWORD_RESET) {
            $mapData = [];
            $message = 'Impossible de réinitialiser le mot de passe.';
            $http = 422;

            switch ($status) {
                case Password::INVALID_TOKEN:
                    $mapData = ['token' => ['Le lien de réinitialisation est invalide ou a expiré.']];
                    $message = 'Lien invalide ou expiré. Obtenez un nouveau lien.';
                    break;

                case Password::INVALID_USER:
                    $mapData = ['email' => ["Cette adresse email ne correspond pas à la demande de réinitialisation."]];
                    $message = 'Adresse email invalide pour cette demande.';
                    break;

                case Password::RESET_THROTTLED: // selon version Laravel
                    $mapData = ['email' => ['Veuillez patienter avant de réessayer.']];
                    $message = 'Vous avez tenté trop de fois. Réessayez plus tard.';
                    $http = 429;
                    break;

                default:
                    $mapData = ['email' => [__($status)]]; // fallback propre
                    $message = 'Réinitialisation impossible pour le moment.';
                    break;
            }

            return $this->responseJson(false, $message, $mapData, $http);
        }

        // 4) Succès
        $user = User::where('email', $request->email)->first();
        $payload = ['user' => $user];

        // Auto-login si l'email était vérifié
        if ($user && $user->hasVerifiedEmail()) {
            $payload['access_token'] = $user->createToken('access_token')->plainTextToken;
        }

        return $this->responseJson(true, 'Mot de passe réinitialisé.', $payload);
    }
}
