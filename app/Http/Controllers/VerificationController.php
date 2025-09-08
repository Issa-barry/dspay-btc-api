<?php
 namespace App\Http\Controllers;

use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VerificationController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        if (!$request->hasValidSignature()) {
            return response()->json([
                'success' => false,
                'message' => 'Le lien de vérification a expiré ou est invalide. Veuillez demander un nouveau lien.'
            ], 403); // Erreur 403 pour lien expiré
        }

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email déjà vérifié.'
            ]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            $user->statut = 'active'; // Changer le statut
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Email vérifié avec succès.'
        ]);
    }

    /**
     * Resend the email verification link.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email déjà vérifié.'
            ], 400);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Lien de vérification envoyé à nouveau.'
        ]);
    }
}
