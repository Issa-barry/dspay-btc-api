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
        return response()->json([
        'success' => true,
        'message' => 'pong',
        'time' => now()->toDateTimeString(),
    ]);
        // // ✅ Vérifie si l'utilisateur est authentifié
        // if (!Auth::check()) { 
        //     // Détruit complètement la session invalide
        //     try {
        //         $request->session()->invalidate();
        //         $request->session()->regenerateToken();
        //     } catch (\Throwable $e) {
        //         Log::warning('Erreur lors de la destruction de session : ' . $e->getMessage());
        //     }
            
        //     return $this->responseJson(
        //         false, 
        //         'Non authentifié. Votre session a expiré.', 
        //         null, 
        //         401
        //     );
        // }

        // // ✅ Récupère l'utilisateur connecté et masque les champs sensibles
        // $user = $request->user()->makeHidden(['password', 'remember_token']);
        
        // return $this->responseJson(
        //     true, 
        //     'Profil récupéré.', 
        //     ['user' => $user]
        // );
    }
}