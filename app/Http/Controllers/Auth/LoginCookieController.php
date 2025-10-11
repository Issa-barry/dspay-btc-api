<?php
// app/Http/Controllers/Auth/LoginCookieController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;
use Illuminate\Support\Facades\Log;

class LoginCookieController extends Controller
{
    use JsonResponseTrait;

    /**
     * Login "session-based":
     * - Requiert le middleware "web" (cookies, session, CSRF).
     * - Vérifie XSRF (géré par VerifyCsrfToken).
     * - Pas de token Bearer : l'auth est portée par le cookie de session HttpOnly.
     */
    public function __invoke(Request $request)
    {
        // 1) Validation
        $v = Validator::make($request->all(), [
            'email'    => ['required','email'],
            'password' => ['required','string'],
        ], [
            'email.required'    => "L'adresse email est obligatoire.",
            'email.email'       => "Le format de l'adresse email est invalide.",
            'password.required' => 'Le mot de passe est obligatoire.',
        ]);

        if ($v->fails()) {
            return $this->responseJson(false, 'Échec de validation.', $v->errors(), 422);
        }

        // 2) Tentative de connexion via le guard "web"
        if (! Auth::attempt($request->only('email','password'), true)) {
            return $this->responseJson(false, 'Email ou mot de passe incorrect.', null, 401);
        }

        try {
            // 3) Sécurité session
            $request->session()->regenerate(); // nouveau ID de session (anti fixation)
        } catch (Throwable $e) {
            Log::error('Regeneration session error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->responseJson(false, 'Erreur de session. Réessayez.', null, 500);
        }

        // 4) Récup user + masquer champs sensibles
        $user = Auth::user()->makeHidden(['password','remember_token']);

        // ⚠️ Les cookies (session + XSRF-TOKEN) sont ajoutés par le middleware "web".
        return $this->responseJson(true, 'Connexion réussie.', [
            'user' => $user,
        ], 200);
    }

  
    public function index(): JsonResponse
    {
        return $this->responseJson(true, 'Test endpoint login2 fonctionne.');
    }
}
