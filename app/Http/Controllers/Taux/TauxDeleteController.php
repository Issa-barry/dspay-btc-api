<?php

namespace App\Http\Controllers\Taux;

use App\Http\Controllers\Controller;
use App\Models\TauxEchange;
use App\Traits\JsonResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Exception;

class TauxDeleteController extends Controller
{
    use JsonResponseTrait;

    /**
     * Supprimer un taux de change.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deleteById($id)
    {
        try {
            // Trouver le taux de change
            $tauxEchange = TauxEchange::findOrFail($id);

            // Vérifier s'il est utilisé quelque part avant suppression (optionnel)
            if ($tauxEchange->transferts()->exists()) {
                return $this->responseJson(false, 'Impossible de supprimer ce taux de change, il est utilisé dans un transfert.', null, 409);
            }

            // Suppression
            $tauxEchange->delete();

            return $this->responseJson(true, 'Taux de change supprimé avec succès.');
        } catch (ModelNotFoundException $e) {
            return $this->responseJson(false, 'Taux de change non trouvé.', null, 404);
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la suppression du taux de change.', $e->getMessage(), 500);
        }
    }
}
