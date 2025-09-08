<?php

namespace App\Http\Controllers\Transfert;

use App\Http\Controllers\Controller;
use App\Models\Transfert;
use App\Traits\JsonResponseTrait;
use Exception;
use Illuminate\Http\Request;

class TransfertUpdateController extends Controller
{
   use JsonResponseTrait;

    /**
     * Mettre à jour les informations d'un transfert par son code.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateByCode(Request $request, $code)
    {
        try {
            $transfert = Transfert::where('code', $code)->first();

            if (!$transfert) {
                return $this->responseJson(false, 'Transfert non trouvé.', null, 404);
            }

            $validatedData = $request->validate([
                'receveur_nom_complet' => 'required|string|max:255', 
                'receveur_phone' => 'required|string|max:20',
                'quartier' => 'required|string|max:255',
            ]);

            $transfert->update($validatedData);

            return $this->responseJson(true, 'Transfert mis à jour avec succès.', $transfert);
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la mise à jour.', $e->getMessage(), 500);
        }
    }

    /**
 * Mettre à jour les informations d'un transfert par son ID.
 *
 * @param  \Illuminate\Http\Request  $request
 * @param  int  $id
 * @return \Illuminate\Http\JsonResponse
 */
public function updateById(Request $request, $id)
{
    try {
        // Rechercher le transfert par son ID
        $transfert = Transfert::find($id);

        if (!$transfert) {
            return $this->responseJson(false, 'Transfert non trouvé.', null, 404);
        }

        // Validation des données à mettre à jour
        $validatedData = $request->validate([
            'receveur_nom_complet' => 'required|string|max:255', 
            'receveur_phone' => 'required|string|max:20',
            'quartier' => 'required|string|max:255',
        ]);

        // Mise à jour des champs
        $transfert->update($validatedData);

        return $this->responseJson(true, 'Transfert mis à jour avec succès.', $transfert);
    } catch (Exception $e) {
        return $this->responseJson(false, 'Erreur lors de la mise à jour.', $e->getMessage(), 500);
    }
}

}
