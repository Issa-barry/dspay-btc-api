<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LogoutController extends Controller
{
    use JsonResponseTrait;

    public function __invoke(Request $request)
    {
        // Révoquer tous les tokens de l'utilisateur
        $request->user()->tokens()->delete();

        // Supprimer le cookie avec les mêmes attributs (path/domain)
        $path     = config('session.path', '/');
        $domain   = config('session.domain') ?: parse_url(config('app.url'), PHP_URL_HOST);
        $cookie   = Cookie::forget('access_token', $path, $domain);

        return $this->responseJson(true, 'Déconnexion réussie.', null)
            ->cookie($cookie);
    }
}
