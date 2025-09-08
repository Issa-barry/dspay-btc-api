<?php

namespace App\Http\Controllers\Agence;

use App\Http\Controllers\Controller;
use App\Models\Agence;
use App\Traits\JsonResponseTrait;
use Exception;

class AgenceDeleteController extends Controller
{
    use JsonResponseTrait;  

    /**
     * Supprimer une agence
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteById($id)
    {
        try { 
            if (!is_numeric($id)) {
                return $this->responseJson(false, 'ID invalide.', null, 400);
            }
 
            $agence = Agence::find($id);
            if (!$agence) {
                return $this->responseJson(false, 'Agence non trouvée.', null, 404);
            }
 
            $agence->delete();

            return $this->responseJson(true, 'Agence supprimée avec succès.', null, 200);
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la suppression de l\'agence.', $e->getMessage(), 500);
        }
    }
}
