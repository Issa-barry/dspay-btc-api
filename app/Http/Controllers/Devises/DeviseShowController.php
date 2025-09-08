<?php

namespace App\Http\Controllers\Devises;

use App\Http\Controllers\Controller;
use App\Models\Devise;
use App\Traits\JsonResponseTrait;
 
class DeviseShowController extends Controller
{
    use JsonResponseTrait;

    /**
     * Récupérer toutes les devises
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $devises = Devise::all();

        return $this->responseJson(true, 'Liste des devises récupérée avec succès.', $devises);
    }

    /**
     * Récupérer une devise par son ID
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getById($id)
    {
        $devise = Devise::find($id);

        if (!$devise) {
            return $this->responseJson(false, 'Devise non trouvée.', null, 404);
        }

        return $this->responseJson(true, 'Devise récupérée avec succès.', $devise);
    }
}
