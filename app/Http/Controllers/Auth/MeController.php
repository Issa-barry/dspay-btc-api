<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MeController extends Controller
{
    use JsonResponseTrait;

    public function __invoke(Request $request)
    {
        // ✅ En API Sanctum, ne pas utiliser la session
        // On s'appuie sur l'utilisateur déjà authentifié par 'auth:sanctum'
        $user = $request->user();

        if (!$user) {
            return $this->responseJson(false, 'Non authentifié.', null, 401);
        }

        // Masquer les champs sensibles
        $user = $user->makeHidden(['password', 'remember_token']);
        
        return $this->responseJson(
            true, 
            'Profil récupéré.', 
            ['user' => $user]
        );
    }

   public function index()
    {
        return $this->responseJson(true, 'Test endpoint fonctionne.');
    }
}
