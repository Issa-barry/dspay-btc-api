<?php

namespace App\Http\Controllers\Frais;

use App\Http\Controllers\Controller;
use App\Models\Frais;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Traits\JsonResponseTrait;

class FraisDeleteController extends Controller
{
    use JsonResponseTrait;

    /**
     * Supprimer un frais existant.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deleteById($id)
    {
        try {
            // Trouver le frais
            $frais = Frais::findOrFail($id);
            
            // Supprimer le frais
            $frais->delete();
            
            return $this->responseJson(true, 'Frais supprimé avec succès.');
        } catch (ModelNotFoundException $e) {
            return $this->responseJson(false, 'Frais non trouvé.', null, 404);
        } catch (\Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la suppression du frais.', $e->getMessage(), 500);
        }
    }
}
