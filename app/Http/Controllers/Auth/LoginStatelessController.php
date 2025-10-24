<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginStatelessController extends Controller
{
    use JsonResponseTrait;

    public function __invoke(Request $request): JsonResponse
    {
        // 1️⃣ Validation des champs
        $validator = Validator::make($request->all(), [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ], [
            'email.required'    => "L'adresse email est obligatoire.",
            'email.email'       => "Le format de l'adresse email est invalide.",
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min'      => 'Le mot de passe doit contenir au moins 6 caractères.',
        ]);

        if ($validator->fails()) {
            return $this->responseJson(false, 'Échec de validation.', $validator->errors(), 422);
        }

        // 2️⃣ Vérifie si le user existe et si le mot de passe est correct
        $user = User::where('email', $request->email)->first();

        // ⚠️ Sécurité : même message pour mail inexistant ou mot de passe erroné
        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->responseJson(
                false,
                'Email ou mot de passe incorrect.',
                null,
                401
            );
        }

        // 3️⃣ Vérifie si l’adresse email a été confirmée
        if (!$user->hasVerifiedEmail()) {
            return $this->responseJson(
                false,
                "Veuillez vérifier votre email avant de vous connecter.",
                ['email' => $user->email],
                403
            );
        }

        // 4️⃣ (Optionnel) Révoquer les anciens tokens si tu veux une seule session active
        // $user->tokens()->delete();

        // 5️⃣ Génération du token avec expiration (30 min)
        $expiresAt = now()->addMinutes(120);
        $token = $user->createToken('access_token', ['*'], $expiresAt);

        // 6️⃣ Masquer les infos sensibles
        $user->makeHidden(['password', 'remember_token']);

        // 7️⃣ Réponse OK
        return $this->responseJson(true, 'Connexion réussie.', [
            'user'         => $user,
            'access_token' => $token->plainTextToken,
            'token_type'   => 'Bearer',
            'expires_in'   => 1800,
            'expires_at'   => $expiresAt->toIso8601String(),
        ], 200);
    }

    public function index(): JsonResponse
    {
        return $this->responseJson(true, 'LoginStatelessController fonctionne correctement.');
    }
}
