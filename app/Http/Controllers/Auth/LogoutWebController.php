<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogoutWebController extends Controller
{
    use JsonResponseTrait;

    public function __invoke(Request $request)
    {
        try {
            // ✅ Vérifie si l'utilisateur est authentifié
            if (!Auth::guard('web')->check()) {
                // Nettoie quand même la session au cas où
                $this->cleanupSession($request);
                
                return $this->responseJson(
                    false, 
                    'Aucune session active à déconnecter.', 
                    null, 
                    401
                );
            }

            // Capture l'ID utilisateur avant déconnexion (pour les logs)
            $userId = Auth::id();

            // ✅ Déconnecte l'utilisateur de la session web
            Auth::guard('web')->logout();

            // ✅ Invalide complètement la session
            $request->session()->invalidate();

            // ✅ Régénère le token CSRF pour sécurité
            $request->session()->regenerateToken();

            // ✅ Supprime le cookie de session
            $cookieName = config('session.cookie', 'laravel_session');
            $cookie = cookie()->forget($cookieName);

            // Log succès
            Log::info('Déconnexion web réussie', [
                'user_id' => $userId,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->responseJson(true, 'Déconnexion réussie.')
                ->withCookie($cookie);

        } catch (Throwable $e) {
            // ✅ Log détaillé de l'erreur
            Log::error('Erreur critique lors de la déconnexion web', [
                'error_message' => $e->getMessage(),
                'user_id' => Auth::id(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // ✅ Tentative de nettoyage forcé (fail-safe)
            $cleanupSuccess = $this->forceCleanup($request);

            // Détermine le message selon le succès du nettoyage
            $message = $cleanupSuccess 
                ? 'Erreur lors de la déconnexion, mais session nettoyée avec succès.'
                : 'Erreur critique lors de la déconnexion. Veuillez vider le cache de votre navigateur.';

            return $this->responseJson(
                $cleanupSuccess, 
                $message, 
                config('app.debug') ? ['error' => $e->getMessage()] : null,
                500
            );
        }
    }

    /**
     * Nettoie proprement la session
     * 
     * @param Request $request
     * @return void
     */
    private function cleanupSession(Request $request): void
    {
        try {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } catch (Throwable $e) {
            Log::warning('Impossible de nettoyer la session normalement', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Force le nettoyage complet en cas d'erreur
     * 
     * @param Request $request
     * @return bool Succès du nettoyage
     */
    private function forceCleanup(Request $request): bool
    {
        $success = true;

        // Tentative 1 : Logout
        try {
            if (Auth::guard('web')->check()) {
                Auth::guard('web')->logout();
            }
        } catch (Throwable $e) {
            Log::error('Échec du logout forcé', ['error' => $e->getMessage()]);
            $success = false;
        }

        // Tentative 2 : Flush session
        try {
            $request->session()->flush();
        } catch (Throwable $e) {
            Log::error('Échec du flush session', ['error' => $e->getMessage()]);
            $success = false;
        }

        // Tentative 3 : Invalidate session
        try {
            $request->session()->invalidate();
        } catch (Throwable $e) {
            Log::error('Échec de l\'invalidation session', ['error' => $e->getMessage()]);
            $success = false;
        }

        // Tentative 4 : Régénération token CSRF
        try {
            $request->session()->regenerateToken();
        } catch (Throwable $e) {
            Log::error('Échec de la régénération du token CSRF', ['error' => $e->getMessage()]);
            // Non critique, ne change pas $success
        }

        return $success;
    }
}