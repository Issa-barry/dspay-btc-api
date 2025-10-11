<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateFromCookie
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si pas de token dans le header Authorization
        if (!$request->bearerToken()) {
            // Chercher le token dans le cookie
            $token = $request->cookie('access_token');
            // Décoder au cas où le client a encodé l'URL (ex: %7C)
            if (is_string($token)) {
                $token = urldecode($token);
                $token = trim($token);
            }

            if (!empty($token)) {
                // Ajouter le token au header Authorization pour que Sanctum puisse l'utiliser
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }
        }

        return $next($request);
    }
}
