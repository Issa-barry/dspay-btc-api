<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\JsonResponseTrait;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

class VerifyEmailController extends Controller
{
    use JsonResponseTrait;

    public function __invoke($id, $hash)
    {
        $user = User::findOrFail($id);

        if (hash_equals($hash, sha1($user->getEmailForVerification()))) {
            if (!$user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
                event(new Verified($user));
            }
            return $this->responseJson(true, 'Email vérifié avec succès.', ['user' => $user]);
        }

        return $this->responseJson(false, 'Le lien de vérification est invalide.', null, 400);
    }
}
