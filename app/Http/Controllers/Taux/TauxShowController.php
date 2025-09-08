<?php

namespace App\Http\Controllers\Taux;

use App\Http\Controllers\Controller;
use App\Models\TauxEchange;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;

class TauxShowController extends Controller
{
    use JsonResponseTrait;

    /**
     * Afficher tous les taux de change.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $tauxEchanges = TauxEchange::with(['deviseSource', 'deviseCible'])->get();
            return $this->responseJson(true, 'Liste des taux de change récupérée avec succès.', $tauxEchanges);
        } catch (\Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la récupération des taux de change.', $e->getMessage(), 500);
        }
    }

    /** 
     * Afficher un taux de change spécifique.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getById($id)
    {
        try {
            // Vérifier que l'ID est valide
            if (!is_numeric($id)) {
                return $this->responseJson(false, 'ID invalide.', null, 400);
            }

            $tauxEchange = TauxEchange::with(['deviseSource', 'deviseCible'])->find($id);

            if (!$tauxEchange) {
                return $this->responseJson(false, 'Taux de change non trouvé.', null, 404);
            }

            return $this->responseJson(true, 'Taux de change récupéré avec succès.', $tauxEchange);
        } catch (\Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la récupération du taux de change.', $e->getMessage(), 500);
        }
    }
}
