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
            'email'        => ['nullable', 'email', 'required_without:phone'],
            'phone'        => ['nullable', 'string', 'required_without:email'],
            'password'     => ['required', 'string', 'min:8'],
            'country_code' => ['nullable', 'string', 'size:2'], // ✅ ISO2 : FR, GN, YT, RE
        ], [
            'email.email'               => "Le format de l'adresse email est invalide.",
            'email.required_without'    => "L'adresse email ou le numéro de téléphone est obligatoire.",
            'phone.required_without'    => "L'adresse email ou le numéro de téléphone est obligatoire.",
            'password.required'         => 'Le mot de passe est obligatoire.',
            'password.min'              => 'Le mot de passe doit contenir au moins 8 caractères.',
            'country_code.size'         => 'Le code pays doit contenir exactement 2 caractères.',
        ]);

        if ($validator->fails()) {
            return $this->responseJson(false, 'Échec de validation.', $validator->errors(), 422);
        }

        // 2) Récupération utilisateur
        try {
            $user = null;

            if ($request->filled('email')) {
                // Recherche par email
                $user = User::where('email', $request->email)->first();
                
                Log::debug('Login attempt with email', [
                    'email' => $request->email,
                    'user_found' => $user ? $user->id : null
                ]);
            } 
            elseif ($request->filled('phone')) {
                $countryCode = $request->input('country_code');
                
                // ✅ Normaliser avec country_code pour précision maximale
                $normalizedPhone = User::normalizePhone(
                    $request->phone,
                    null,
                    $countryCode
                );
                
                // ✅ Rechercher avec phone ET country_code (distinguer Mayotte/Réunion par ex)
                $query = User::where('phone', $normalizedPhone);
                
                if ($countryCode) {
                    $query->where('country_code', strtoupper($countryCode));
                }
                
                $user = $query->first();
                
                // 📝 Log détaillé pour debug
                Log::debug('Login attempt with phone', [
                    'phone_received' => $request->phone,
                    'country_code' => $countryCode,
                    'phone_normalized' => $normalizedPhone,
                    'query_country_code' => $countryCode ? strtoupper($countryCode) : null,
                    'user_found' => $user ? $user->id : null,
                    'user_phone' => $user ? $user->phone : null,
                    'user_country' => $user ? $user->country_code : null
                ]);
            }
        } catch (QueryException|PDOException|Throwable $e) {
            Log::error('DB error during login lookup', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'phone' => $request->phone ?? null,
                'email' => $request->email ?? null
            ]);
            return $this->responseJson(false, 'Service temporairement indisponible.', null, 503);
        }

        // 3) Vérification identifiants
        if (!$user || !Hash::check($request->password, $user->password)) {
            Log::warning('Login failed - Invalid credentials', [
                'phone' => $request->phone ?? null,
                'email' => $request->email ?? null,
                'country_code' => $request->country_code ?? null,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            return $this->responseJson(
                false, 
                'Identifiant ou mot de passe incorrect.',
                null,
                401
            );
        }

        // 4) Vérification statut du compte
        if (isset($user->statut) && $user->statut === 'bloque') {
            Log::warning('Login attempt on blocked account', [
                'user_id' => $user->id,
                'reference' => $user->reference,
                'ip' => $request->ip()
            ]);
            
            return $this->responseJson(
                false,
                'Votre compte a été bloqué. Veuillez contacter le support.',
                null,
                403
            );
        }

        if (isset($user->statut) && $user->statut === 'archive') {
            return $this->responseJson(
                false,
                'Ce compte a été archivé.',
                null,
                403
            );
        }

        // 5) Vérification d'email (optionnel)
        // if (!$user->hasVerifiedEmail()) {
        //     return $this->responseJson(
        //         false,
        //         "Veuillez vérifier votre email avant de vous connecter.",
        //         ['email' => $user->email],
        //         403
        //     );
        // }

        // 6) Création du token
        $expiresAt = now()->addMinutes(120);
        try {
            $token = $user->createToken('access_token', ['*'], $expiresAt);
        } catch (Throwable $e) {
            Log::error('Token creation failed', [
                'exception' => $e->getMessage(), 
                'user_id' => $user->id
            ]);
            return $this->responseJson(false, 'Service temporairement indisponible.', null, 503);
        }

        // 7) Masquer les infos sensibles
        $user->makeHidden(['password', 'remember_token']);

        // 8) Log de connexion réussie
        Log::info('User logged in successfully', [
            'user_id' => $user->id,
            'reference' => $user->reference,
            'phone' => $user->phone,
            'email' => $user->email,
            'country_code' => $user->country_code,
            'pays' => $user->pays,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        // 9) Réponse OK
        $expiresIn = now()->diffInSeconds($expiresAt);

        return $this->responseJson(true, 'Connexion réussie.', [
            'user'         => $user,
            'access_token' => $token->plainTextToken,
            'token_type'   => 'Bearer',
            'expires_in'   => $expiresIn,
            'expires_at'   => $expiresAt->toIso8601String(),
        ], 200);
    }

    /**
     * Test endpoint
     */
    public function index(): JsonResponse
    {
        return $this->responseJson(true, 'LoginStatelessController fonctionne correctement.');
    }
}