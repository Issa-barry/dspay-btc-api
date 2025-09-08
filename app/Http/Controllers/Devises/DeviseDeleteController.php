<?php

namespace App\Http\Controllers\Devises;

use App\Http\Controllers\Controller;
use App\Models\Devise;
use App\Traits\JsonResponseTrait;
use Exception;

class DeviseDeleteController extends Controller
{
    use JsonResponseTrait;

    /**
     * Supprimer une devise par ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteById($id)
    {
        try {
            $devise = Devise::find($id);

            if (!$devise) {
                return $this->responseJson(false, 'Devise non trouvée.', null, 404);
            }

            $devise->delete();

            return $this->responseJson(true, 'Devise supprimée avec succès.');
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la suppression de la devise.', $e->getMessage(), 500);
        }
    }
}
