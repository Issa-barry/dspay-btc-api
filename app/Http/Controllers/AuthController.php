<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role;
use Str;

class AuthController extends Controller
{

    protected function responseJson($success, $message, $data = null, $statusCode = 200)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    
    // Connexion
    public function login(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'email' => 'required|email',
                'password' => 'required|string',
            ],
            [
                'email.required' => 'L\'adresse email est obligatoire.',
                'email.email' => 'Le format de l\'adresse email est invalide.',
                'password.required' => 'Le mot de passe est requis.',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Les informations de connexion sont incorrectes.'], 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Votre email n\'a pas été vérifié. Vérifiez votre email et essayez à nouveau.',
                'email' => $user->email
            ], 400);
        }

        $token = $user->createToken('access_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie.',
            'user' => $user,
            'access_token' => $token,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json([
            'message' => 'Déconnecté de tous les appareils.',
        ], 200);
    }

    // App\Http\Controllers\AuthController.php
public function me(Request $request)
{
    $user = $request->user(); // récupère l'utilisateur via Sanctum

    // Cache les champs sensibles
    $user->makeHidden(['password', 'remember_token']);

    return response()->json([
        'success' => true,
        'message' => 'Profil récupéré.',
        'data' => $user
    ], 200);
}


    // Vérification de l'email
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (hash_equals($hash, sha1($user->getEmailForVerification()))) {
            $user->markEmailAsVerified();

            // Déclenche l'événement de vérification
            event(new Verified($user));

            return response()->json([
                'message' => 'Email vérifié avec succès.',
                'user' => $user
            ], 200);
        }

        return response()->json([
            'error' => 'Le lien de vérification est invalide.',
        ], 400);
    }

public function resetPassword(Request $request)
{
    $messages = [
        'email.required' => 'L\'adresse email est obligatoire.',
        'email.email' => 'L\'adresse email n\'est pas valide.',
        'token.required' => 'Le jeton est manquant.',
        'password.required' => 'Le mot de passe est obligatoire.',
        'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
    ];

    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|string|min:8|confirmed',
    ], $messages);

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) {
            // ➜ préserver l’état de vérification existant
            // $verifiedAt = $user->getOriginal('email_verified_at');

            $user->forceFill([
                'password'          => Hash::make($password),
                'remember_token'    => Str::random(60),
                // 'email_verified_at' => $verifiedAt,
            ])->saveQuietly();

            event(new PasswordReset($user));
        }
    );

    if ($status !== Password::PASSWORD_RESET) {
        return $this->responseJson(false, __($status), null, 422);
    }

    $user = User::where('email', $request->email)->first();

    // ✅ BONUS : si l’email était déjà vérifié, on émet un token pour auto-login
    $payload = ['user' => $user];
    if ($user && $user->hasVerifiedEmail()) {
        $payload['access_token'] = $user->createToken('access_token')->plainTextToken;
    }

    return $this->responseJson(true, 'Mot de passe réinitialisé.', $payload);
}


    public function sendResetPasswordLink(Request $request)
    {
        // Validation avec messages personnalisés
        $validator = Validator::make(
            $request->all(),
            [
                'email' => 'required|email|exists:users,email',
            ],
            [
                'email.exists' => 'Aucun utilisateur n\'est enregistré avec cette adresse email.',
                'email.required' => 'L\'adresse email est obligatoire.',
                'email.email' => 'Le format de l\'adresse email est invalide.',
            ]
        );

        // Si la validation échoue
        if ($validator->fails()) {
            return $this->responseJson(
                false,
                'Échec de validation.',
                $validator->errors(),
                422
            );
        }

        // Envoi du lien
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return $this->responseJson(
                true,
                'Lien de réinitialisation envoyé à votre email.'
            );
        }

        // En cas d'échec (ex : email non envoyé)
        return $this->responseJson(
            false,
            'Une erreur est survenue lors de l\'envoi du lien.',
            ['email' => [__($status)]],
            500
        );
    }



    /**
     * Renvoie l'email de validation à un utilisateur.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendVerificationEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => $validator->errors()
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'This email is already verified.'
            ], 200);
        }

        // Déclencher l'événement Registered pour renvoyer l'email
        event(new Registered($user));

        return response()->json([
            'message' => 'Verification email resent successfully.'
        ], 200);
    }

    // Vérification du token dans l'en-tête
    public function checkTokenInHeader(Request $request)
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json(['error' => 'Token manquant dans l\'en-tête.'], 422);
        }

        if (str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
        }

        $tokenExists = PersonalAccessToken::where('token', hash('sha256', $token))->exists();

        if (!$tokenExists) {
            return response()->json(['message' => 'Token invalide ou inexistant.'], 404);
        }

        return response()->json(['message' => 'Token valide.'], 200);
    }
}
