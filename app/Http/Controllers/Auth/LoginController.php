<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\JsonResponseTrait;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cookie;

class LoginController extends Controller
{
    use JsonResponseTrait;

    public function __invoke(Request $request)
    {
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

        $user = User::where('email', $request->email)->first();
        
        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->responseJson(false, 'Email ou mot de passe incorrect.', null, 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return $this->responseJson(false, "Votre email n'a pas été vérifié. Vérifiez votre email et essayez à nouveau.", [
                'email' => $user->email
            ], 400);
        }

        // Créer le token Sanctum
        $token = $user->createToken('access_token')->plainTextToken;

        // Paramètres issus de la config pour éviter le hard‑code
        $minutes   = (int) config('session.lifetime', 120); // minutes
        $path      = config('session.path', '/');
        $domain    = config('session.domain')
            ?: parse_url(config('app.url'), PHP_URL_HOST); // ex: 127.0.0.1
        $secure    = (bool) config('session.secure', false);
        $sameSite  = config('session.same_site', 'lax');    // 'lax' | 'strict' | 'none'

        // Créer le cookie HttpOnly
        $cookie = Cookie::make(
            'access_token',
            $token,
            $minutes,
            $path,
            $domain,
            $secure,
            true,      // HttpOnly
            false,     // Raw
            $sameSite
        );

        // Retourner la réponse avec le cookie (Set-Cookie)
        return $this->responseJson(true, 'Connexion réussie.', [
            'user' => $user,
            // ⚠️ Ne PAS inclure le token dans le JSON pour la sécurité
        ])->cookie($cookie); // ✅ Attacher le cookie à la réponse
    }
}
