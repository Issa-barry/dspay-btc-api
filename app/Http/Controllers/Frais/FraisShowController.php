<?php

namespace App\Http\Controllers\Frais;

use App\Http\Controllers\Controller;
use App\Models\Frais;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;

class FraisShowController extends Controller
{
    use JsonResponseTrait;

    /**
     * Afficher tous les frais.
     */
    public function index()
    {
        $frais = Frais::all();
        return $this->responseJson(true, 'Liste des frais récupérée avec succès.', $frais);
    }

      /**
     * Afficher un frais spécifique.
     */
    public function show($id)
    {
        $frais = Frais::find($id);
        if (!$frais) {
            return $this->responseJson(false, 'Frais non trouvé.', null, 404);
        }
        return $this->responseJson(true, 'Frais récupéré avec succès.', $frais);
    }
}
