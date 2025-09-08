<?php

namespace App\Http\Controllers\Transfert;

use App\Http\Controllers\Controller;
use App\Models\Transfert;
use App\Traits\JsonResponseTrait;
use Exception;
use Illuminate\Http\Request;

class TransfertShowController extends Controller
{
     use JsonResponseTrait;

    /**
     * Afficher tous les transferts.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $transferts = Transfert::with(['deviseSource', 'deviseCible', 'tauxEchange'])->get();
            
            // Masquer le code si le statut est 'en_cours' pour chaque transfert
            foreach ($transferts as $transfert) {
                if ($transfert->statut === 'en_cours') {
                    $transfert->makeHidden('code');
                }
            }

            return $this->responseJson(true, 'Liste des transferts récupérée avec succès.', $transferts);
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la récupération des transferts.', $e->getMessage(), 500);
        }
    }

    /**
     * Afficher un transfert spécifique.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $transfert = Transfert::with(['deviseSource', 'deviseCible', 'tauxEchange'])->find($id);

            if (!$transfert) {
                return $this->responseJson(false, 'Transfert non trouvé.', null, 404);
            }

            // Masquer le code si le statut n'est pas "retiré"
            if ($transfert->statut === 'en_cours') {
                $transfert->makeHidden('code');
            }

            return $this->responseJson(true, 'Transfert récupéré avec succès.', $transfert);
            // return $this->responseJson(true, 'Transfert effectué avec succès.', [
            //     'transfert' => $transfert,
            //     'agent' => $transfert->agent ? $transfert->agent->nom_complet : 'Non assigné'
            // ], 201);
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la récupération du transfert.', $e->getMessage(), 500);
        }
    } 

    /**
     * Afficher un transfert spécifique par son code.
     *
     * @param  string  $code
     * @return \Illuminate\Http\Response
     */
    public function showByCode($code)
    {
        try {
            // Recherche du transfert par code
            $transfert = Transfert::with(['deviseSource', 'deviseCible', 'tauxEchange'])
                ->where('code', $code)
                ->first();

            if (!$transfert) {
                return $this->responseJson(false, 'Transfert non trouvé.', null, 404);
            }

            // Masquer le code si le statut n'est pas "retiré"
            if ($transfert->statut === 'en_cours') {
                $transfert->makeHidden('code');
            }

            return $this->responseJson(true, 'Transfert récupéré avec succès.', $transfert);
            // return $this->responseJson(true, 'Transfert effectué avec succès.', [
            //     'transfert' => $transfert,
            //     'agent' => $transfert->agent ? $transfert->agent->nom_complet : 'Non assigné'
            // ], 201);
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la récupération du transfert.', $e->getMessage(), 500);
        }
    }
}
