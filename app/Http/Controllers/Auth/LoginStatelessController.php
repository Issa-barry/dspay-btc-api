<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * LoginStatelessController - Authentification Token Pure (Stateless)
 * 
 * Différences avec LoginBearerController :
 * - Gestion d'expiration des tokens
 * - Support refresh token (optionnel)
 * - Révocation des anciens tokens
 * - Plus de métadonnées sur le token
 */
class LoginStatelessController extends Controller
{
    use JsonResponseTrait;

    /**
     * Login stateless avec token Sanctum
     * 
     * Flow :
     * 1. Validation credentials
     * 2. Vérification user + password
     * 3. Génération token avec expiration
     * 4. Retour token + user en JSON
     * 
     * Usage Frontend :
     * - Stocker token dans localStorage/IndexedDB
     * - Envoyer dans header : Authorization: Bearer {token}
     * - Pas de cookies, pas de CSRF
     */
    public function __invoke(Request $request): JsonResponse
    {
        // 1) Validation
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
            return $this->responseJson(
                false, 
                'Échec de validation.', 
                $validator->errors(), 
                422
            );
        }

        // 2) Vérification utilisateur
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->responseJson(
                false, 
                'Identifiants incorrects.', 
                null, 
                401
            );
        }

        // 3) Vérification email (optionnel)
        if (!$user->hasVerifiedEmail()) {
            return $this->responseJson(
                false, 
                "Veuillez vérifier votre email avant de vous connecter.", 
                ['email' => $user->email], 
                403
            );
        }

        // 4) Révocation des anciens tokens (optionnel - limite à 1 session active)
        // Décommenter si vous voulez forcer une seule session :
        // $user->tokens()->delete();

        // 5) Création du token avec expiration (30 minutes)
        $expiresAt = now()->addMinutes(30);
        
        $token = $user->createToken(
            'access_token',           // Nom du token
            ['*'],                    // Abilities (permissions)
            $expiresAt                // Expiration
        );

        // 6) Masquer les champs sensibles
        $user->makeHidden(['password', 'remember_token']);

        // 7) Réponse avec token
        return $this->responseJson(
            true, 
            'Connexion réussie.', 
            [
                'user'         => $user,
                'access_token' => $token->plainTextToken,
                'token_type'   => 'Bearer',
                'expires_in'   => 1800, // 30 minutes en secondes
                'expires_at'   => $expiresAt->toIso8601String(),
            ], 
            200
        );
    }

    /**
     * Endpoint de test
     */
    public function index(): JsonResponse
    {
        return $this->responseJson(
            true, 
            'LoginStatelessController fonctionne correctement.'
        );
    }
}