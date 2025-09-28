<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str; // ✅ bon import
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    protected function responseJson($success, $message, $data = null, $statusCode = 200)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data'    => $data
        ], $statusCode);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Connexion
    public function login(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'email'    => 'required|email',
                'password' => 'required|string',
            ],
            [
                'email.required'    => 'L\'adresse email est obligatoire.',
                'email.email'       => 'Le format de l\'adresse email est invalide.',
                'password.required' => 'Le mot de passe est requis.',
            ]
        );

        if ($validator->fails()) {
            return $this->responseJson(false, 'Échec de validation.', $validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->responseJson(false, 'Les informations de connexion sont incorrectes.', null, 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return $this->responseJson(false, 'Votre email n\'a pas été vérifié. Vérifiez votre email et essayez à nouveau.', [
                'email' => $user->email
            ], 400);
        }

        $token = $user->createToken('access_token')->plainTextToken;

        return $this->responseJson(true, 'Connexion réussie.', [
            'user'         => $user,
            'access_token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return $this->responseJson(true, 'Déconnecté de tous les appareils.');
    }

    // Profil courant
    public function me(Request $request)
    {
        $user = $request->user();
        $user->makeHidden(['password', 'remember_token']);

        return $this->responseJson(true, 'Profil récupéré.', $user);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Vérification de l'email
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (hash_equals($hash, sha1($user->getEmailForVerification()))) {
            if (!$user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
                event(new Verified($user));
            }

            return $this->responseJson(true, 'Email vérifié avec succès.', [
                'user' => $user
            ]);
        }

        return $this->responseJson(false, 'Le lien de vérification est invalide.', null, 400);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Réinitialisation du mot de passe (lien utilisable une seule fois)
    public function resetPassword(Request $request)
    {
        $messages = [
            'email.required'    => 'L\'adresse email est obligatoire.',
            'email.email'       => 'L\'adresse email n\'est pas valide.',
            'token.required'    => 'Le lien de réinitialisation est invalide ou expiré.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min'      => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ];

        $request->validate([
            'token'                 => 'required',
            'email'                 => 'required|email',
            'password'              => 'required|string|min:8|confirmed',
        ], $messages);

        // ➜ Password::reset vérifie le token ET le consomme (usage unique)
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                // ⚠️ Conserver l’état de vérification existant
                $verifiedAt = $user->getOriginal('email_verified_at');

                $user->forceFill([
                    'password'          => Hash::make($password),
                    'remember_token'    => Str::random(60),
                    'email_verified_at' => $verifiedAt, // ✅ on remet la valeur telle quelle
                ])->saveQuietly();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            // Exemples: PASSWORD_RESET, INVALID_TOKEN, PASSWORD_BROKER, etc.
            // On renvoie 422 pour permettre l’affichage côté front
            return $this->responseJson(false, __($status), null, 422);
        }

        $user = User::where('email', $request->email)->first();

        // Bonus : si l’email était déjà vérifié, émettre un token pour auto-login
        $payload = ['user' => $user];
        if ($user && $user->hasVerifiedEmail()) {
            $payload['access_token'] = $user->createToken('access_token')->plainTextToken;
        }

        return $this->responseJson(true, 'Mot de passe réinitialisé.', $payload);
    }

    // Envoi du lien de réinitialisation
    public function sendResetPasswordLink(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'email' => 'required|email|exists:users,email',
            ],
            [
                'email.exists'   => 'Aucun utilisateur n\'est enregistré avec cette adresse email.',
                'email.required' => 'L\'adresse email est obligatoire.',
                'email.email'    => 'Le format de l\'adresse email est invalide.',
            ]
        );

        if ($validator->fails()) {
            return $this->responseJson(false, 'Échec de validation.', $validator->errors(), 422);
        }

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return $this->responseJson(true, 'Lien de réinitialisation envoyé à votre email.');
        }

        return $this->responseJson(false, 'Une erreur est survenue lors de l\'envoi du lien.', [
            'email' => [__($status)]
        ], 500);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Renvoyer l’email de vérification
    public function resendVerificationEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->responseJson(false, 'Échec de validation.', $validator->errors(), 400);
        }

        $user = User::where('email', $request->email)->first();

        if ($user->hasVerifiedEmail()) {
            return $this->responseJson(true, 'Cet email est déjà vérifié.');
        }

        event(new Registered($user));

        return $this->responseJson(true, 'Email de vérification renvoyé avec succès.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Vérification d’un token passé en Authorization: Bearer
    public function checkTokenInHeader(Request $request)
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return $this->responseJson(false, 'Token manquant dans l\'en-tête.', null, 422);
        }

        if (str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
        }

        $tokenExists = PersonalAccessToken::where('token', hash('sha256', $token))->exists();

        if (!$tokenExists) {
            return $this->responseJson(false, 'Token invalide ou inexistant.', null, 404);
        }

        return $this->responseJson(true, 'Token valide.');
    }
}
