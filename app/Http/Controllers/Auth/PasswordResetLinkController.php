<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class PasswordResetLinkController extends Controller
{
    use JsonResponseTrait;

    public function __invoke(Request $request)
    {
        $v = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists'   => "Aucun utilisateur n'est enregistré avec cette adresse email.",
            'email.required' => "L'adresse email est obligatoire.",
            'email.email'    => "Le format de l'adresse email est invalide.",
        ]);
        if ($v->fails()) {
            return $this->responseJson(false, 'Échec de validation.', $v->errors(), 422);
        }

        $status = Password::sendResetLink($request->only('email'));
        if ($status === Password::RESET_LINK_SENT) {
            return $this->responseJson(true, 'Lien de réinitialisation envoyé à votre email.');
        }

        return $this->responseJson(false, "Une erreur est survenue lors de l'envoi du lien.", [
            'email' => [__($status)]
        ], 500);
    }
}
