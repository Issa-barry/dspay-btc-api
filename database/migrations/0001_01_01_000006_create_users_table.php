<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Throwable;

class LoginWebController extends Controller
{
    use JsonResponseTrait;

    public function __invoke(Request $request)
    {
        try {
            // 1️⃣ Validation
            $v = Validator::make($request->all(), [
                'email'    => 'required|email',
                'password' => 'required|string',
            ], [
                'email.required'    => "L'adresse email est obligatoire.",
                'email.email'       => "Le format de l'adresse email est invalide.",
                'password.required' => 'Le mot de passe est obligatoire.',
            ]);

            if ($v->fails()) {
                return $this->responseJson(false, 'Échec de validation.', $v->errors(), 422);
            }

            // 2️⃣ Log pour debug
            Log::info('Tentative de connexion', [
                'email' => $request->email,
                'ip' => $request->ip(),
            ]);

            // 3️⃣ Tentative de connexion avec 'remember' = true
            $credentials = $request->only('email', 'password');
            
            if (!Auth::attempt($credentials, true)) {
                Log::warning('Échec authentification', ['email' => $request->email]);
                return $this->responseJson(false, 'Email ou mot de passe incorrect.', null, 401);
            }

            // 4️⃣ Récupérer l'utilisateur AVANT la régénération
            $user = Auth::user();
            
            Log::info('Authentification réussie', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            // 5️⃣ Régénération de la session (protection contre session fixation)
            $request->session()->regenerate();
            
            // 6️⃣ Log après régénération
            Log::info('Session régénérée', [
                'session_id' => $request->session()->getId(),
                'user_authenticated' => Auth::check(),
                'user_id_in_session' => Auth::id(),
            ]);

            // 7️⃣ Vérification email
            if (!$user->hasVerifiedEmail()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                Log::warning('Email non vérifié', ['user_id' => $user->id]);

                return $this->responseJson(false, "Votre email n'a pas été vérifié.", [
                    'email' => $user->email,
                ], 403);
            }

            // 8️⃣ Succès - Recharger l'utilisateur pour être sûr
            $user = Auth::user()->load('role'); // Charger aussi le rôle si nécessaire

            Log::info('Login complet', [
                'user_id' => $user->id,
                'session_id' => $request->session()->getId(),
            ]);

            return $this->responseJson(true, 'Connexion réussie.', [
                'user' => $user,
            ]);

        } catch (Throwable $e) {
            // 9️⃣ Catch global
            Log::error('Erreur interne lors du login web', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responseJson(false, 'Une erreur interne est survenue. Veuillez réessayer plus tard.', null, 500);
        }
    }
}