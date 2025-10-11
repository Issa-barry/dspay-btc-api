<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginBearerController extends Controller
{
    use JsonResponseTrait;

    public function __invoke(Request $request)
    {
        // 1) Validation
        $v = Validator::make($request->all(), [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => "L'adresse email est obligatoire.",
            'email.email'       => "Le format de l'adresse email est invalide.",
            'password.required' => 'Le mot de passe est obligatoire.',
        ]);

        if ($v->fails()) {
            return $this->responseJson(false, 'Échec de validation.', $v->errors(), 422);
        }

        // 2) Auth
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->responseJson(false, 'Email ou mot de passe incorrect.', null, 401);
        }

        // (optionnel) imposer la vérification d’email
        if (!$user->hasVerifiedEmail()) {
            return $this->responseJson(false, "Votre email n'a pas été vérifié.", [
                'email' => $user->email
            ], 403);
        }

        // 3) Créer un Personal Access Token Sanctum
        //    ⚠️ Pas de cookie. On renvoie le token en JSON pour usage "Authorization: Bearer ..."
        $plainTextToken = $user->createToken('access_token')->plainTextToken;

        return $this->responseJson(true, 'Connexion réussie.', [
            'user'         => $user,
            'access_token' => $plainTextToken,
            'token_type'   => 'Bearer',
            // 'expires_in' => 3600, // si vous gérez une expiration custom
        ], 200);
    }
}
