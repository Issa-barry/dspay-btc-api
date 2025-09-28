<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\JsonResponseTrait;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    use JsonResponseTrait;

    public function __invoke(Request $request)
    {
        $request->validate([
            'token'                 => 'required',
            'email'                 => 'required|email',
            'password'              => 'required|string|min:8|confirmed',
        ], [
            'token.required'     => 'Le lien de réinitialisation est invalide ou expiré.',
            'email.required'     => "L'adresse email est obligatoire.",
            'email.email'        => "L'adresse email n'est pas valide.",
            'password.required'  => 'Le mot de passe est obligatoire.',
            'password.min'       => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ]);

        $status = Password::reset(
            $request->only('email','password','password_confirmation','token'),
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

        if ($status !== Password::PASSWORD_RESET) {
            return $this->responseJson(false, __($status), null, 422);
        }

        $user = User::where('email', $request->email)->first();
        $payload = ['user' => $user];

        // Auto-login si l'email était vérifié
        if ($user && $user->hasVerifiedEmail()) {
            $payload['access_token'] = $user->createToken('access_token')->plainTextToken;
        }

        return $this->responseJson(true, 'Mot de passe réinitialisé.', $payload);
    }
}
