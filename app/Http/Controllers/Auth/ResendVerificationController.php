<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\JsonResponseTrait;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResendVerificationController extends Controller
{
    use JsonResponseTrait;

    public function __invoke(Request $request)
    {
        $v = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);
        if ($v->fails()) {
            return $this->responseJson(false, 'Échec de validation.', $v->errors(), 400);
        }

        $user = User::where('email', $request->email)->first();
        if ($user->hasVerifiedEmail()) {
            return $this->responseJson(true, 'Cet email est déjà vérifié.');
        }

        event(new Registered($user));
        return $this->responseJson(true, 'Email de vérification renvoyé avec succès.');
    }
}
