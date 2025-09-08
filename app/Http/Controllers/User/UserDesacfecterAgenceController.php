<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;

class UserDesacfecterAgenceController extends Controller
{
    use JsonResponseTrait;
      /**
     * Désaffecter un utilisateur d'une agence.
     *
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function desaffecterAgence($userId)
    {
        try {
            $user = User::where('id', $userId)->firstOrFail();

            if (is_null($user->agence_id)) {
                return $this->responseJson(false, 'L\'utilisateur n\'est affecté à aucune agence.', null, 400);
            }

            // Supprimer l'affectation
            $user->update(['agence_id' => null]);

            return $this->responseJson(true, 'Utilisateur désaffecté de l\'agence avec succès.', $user, 200);
        } catch (QueryException $e) {
            Log::error('Erreur SQL lors de la désaffectation : ' . $e->getMessage());
            return $this->responseJson(false, 'Erreur de base de données.', null, 500);
        } catch (Exception $e) {
            Log::error('Erreur inattendue lors de la désaffectation : ' . $e->getMessage());
            return $this->responseJson(false, 'Une erreur interne est survenue.', null, 500);
        }
    }
}
