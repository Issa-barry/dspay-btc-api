<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\JsonResponseTrait;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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

        $token = $user->createToken('access_token')->plainTextToken;

        return $this->responseJson(true, 'Connexion réussie.', [
            'user'         => $user,
            'access_token' => $token,
        ]);
    }
}
