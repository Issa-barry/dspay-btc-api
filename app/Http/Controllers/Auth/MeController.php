<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MeController extends Controller
{
    use JsonResponseTrait;

    /**
     * Retourne le profil de l'utilisateur authentifié via Sanctum (Bearer).
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Résolu par le middleware 'auth:sanctum'
        $user = $request->user();

        if (!$user) {
            return $this->responseJson(false, 'Non authentifié.', null, 401);
        }

        return $this->responseJson(true, 'Profil récupéré.', [
            'user' => $user->makeHidden(['password', 'remember_token']),
        ]);
    }

    /**
     * Petit endpoint de test/healthcheck si besoin.
     */
    public function index(): JsonResponse
    {
        return $this->responseJson(true, 'Test endpoint fonctionne.');
    }
}
