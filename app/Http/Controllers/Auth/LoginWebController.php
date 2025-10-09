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

            // 2️⃣ Tentative de connexion
            if (!Auth::attempt($request->only('email', 'password'), true)) {
                return $this->responseJson(false, 'Email ou mot de passe incorrect.', null, 401);
            }

            // 3️⃣ Regénération sécurisée de la session
            try {
                $request->session()->regenerate();
            } catch (Throwable $e) {
                Log::error('Erreur lors de la régénération de la session : ' . $e->getMessage());
                return $this->responseJson(false, 'Erreur de session. Veuillez réessayer.', null, 500);
            }

            $user = Auth::user();

            // 4️⃣ Vérification email
            if (!$user->hasVerifiedEmail()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return $this->responseJson(false, "Votre email n'a pas été vérifié.", [
                    'email' => $user->email,
                ], 403);
            }

            // 5️⃣ Succès
            return $this->responseJson(true, 'Connexion réussie.', [
                'user' => $user,
            ]);

        } catch (Throwable $e) {
            // 6️⃣ Catch global : aucune erreur PHP ne fuit vers le front
            Log::error('Erreur interne lors du login web : ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responseJson(false, 'Une erreur interne est survenue. Veuillez réessayer plus tard.', null, 500);
        }
    }
}
