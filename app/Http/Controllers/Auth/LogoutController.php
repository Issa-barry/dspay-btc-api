<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    use JsonResponseTrait;

    public function __invoke(Request $request)
    {
        // Révoquer le token courant uniquement
        $request->user()->currentAccessToken()?->delete();

        return $this->responseJson(true, 'Déconnexion réussie.');
    }
}
