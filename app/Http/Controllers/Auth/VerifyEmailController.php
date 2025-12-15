<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\JsonResponseTrait;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    use JsonResponseTrait;

    /**
     * Vérifier l'email avec un code à 4 chiffres
     */
    public function verifyWithCode(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:4',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return $this->responseJson(false, 'Utilisateur introuvable.', null, 404);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->responseJson(true, 'Email déjà vérifié.', ['user' => $user]);
        }

        if (!$user->verifyCode($validated['code'])) {
            return $this->responseJson(false, 'Code invalide ou expiré.', null, 400);
        }

        // Marquer comme vérifié et supprimer le code
        $user->markEmailAsVerifiedWithCode();
        event(new Verified($user));

        return $this->responseJson(true, 'Email vérifié avec succès.', ['user' => $user->load('roles')]);
    }

    /**
     * Renvoyer un nouveau code de vérification
     */
    public function resendCode(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return $this->responseJson(false, 'Utilisateur introuvable.', null, 404);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->responseJson(false, 'Email déjà vérifié.', null, 400);
        }

        // Générer un nouveau code
        $user->generateVerificationCode();

        // Envoyer l'email
        try {
            $user->notify(new \App\Notifications\CustomVerifyEmail());
            return $this->responseJson(true, 'Un nouveau code a été envoyé à votre email.', null);
        } catch (\Exception $e) {
            \Log::error("Erreur envoi code : " . $e->getMessage());
            return $this->responseJson(false, "Erreur lors de l'envoi du code.", null, 500);
        }
    }

    /**
     * Ancienne méthode de vérification par lien (conservée pour compatibilité)
     */
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
