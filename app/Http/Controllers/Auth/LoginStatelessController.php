<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use PDOException;
use Throwable;

class LoginStatelessController extends Controller
{
    use JsonResponseTrait;

    public function __invoke(Request $request): JsonResponse
    {
        // 1) Validation
        $validator = Validator::make($request->all(), [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'email.required'    => "L'adresse email est obligatoire.",
            'email.email'       => "Le format de l'adresse email est invalide.",
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min'      => 'Le mot de passe doit contenir au moins 8 caractères.',
        ]);

        if ($validator->fails()) {
            return $this->responseJson(false, 'Échec de validation.', $validator->errors(), 422);
        }

        // 2) Récupération utilisateur (protégée)
        try {
            $user = User::where('email', $request->email)->first();
        } catch (QueryException|PDOException|Throwable $e) {
            Log::error('DB error during login lookup', ['exception' => $e]);
            return $this->responseJson(false, 'Service temporairement indisponible.', null, 503);
        }

        // Même message pour mail inexistant / mauvais mot de passe
        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->responseJson(false, 'Email ou mot de passe incorrect.', null, 401);
        }

        // 3) Vérification d'email
        if (!$user->hasVerifiedEmail()) {
            return $this->responseJson(
                false,
                "Veuillez vérifier votre email avant de vous connecter.",
                ['email' => $user->email],
                403
            );
        }

        // 4) Création du token (protégée)
        $expiresAt = now()->addMinutes(120); // 120 min
        try {
            // Sanctum: createToken($name, $abilities = ['*'], $expiresAt = null)
            $token = $user->createToken('access_token', ['*'], $expiresAt);
        } catch (Throwable $e) {
            Log::error('Token creation failed', ['exception' => $e, 'user_id' => $user->id ?? null]);
            return $this->responseJson(false, 'Service temporairement indisponible.', null, 503);
        }

        // 5) Masquer les infos sensibles
        $user->makeHidden(['password', 'remember_token']);

        // 6) Réponse OK (expires_in cohérent)
        $expiresIn = now()->diffInSeconds($expiresAt);

        return $this->responseJson(true, 'Connexion réussie.', [
            'user'         => $user,
            'access_token' => $token->plainTextToken,
            'token_type'   => 'Bearer',
            'expires_in'   => $expiresIn,
            'expires_at'   => $expiresAt->toIso8601String(),
        ], 200);
    }

    public function index(): JsonResponse
    {
        return $this->responseJson(true, 'LoginStatelessController fonctionne correctement.');
    }
}
