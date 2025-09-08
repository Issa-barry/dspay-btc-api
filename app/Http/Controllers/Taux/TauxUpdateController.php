<?php

namespace App\Http\Controllers\Taux;

use App\Http\Controllers\Controller;
use App\Models\Devise;
use App\Models\TauxEchange;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TauxUpdateController extends Controller
{
    use JsonResponseTrait;

    public function updateById(Request $request, $id)
    {
        try {
            // Validation des données d'entrée
            $validated = $request->validate([
                'taux' => 'required|numeric|min:0',
            ]);

            // Trouver le taux de change existant
            $tauxEchange = TauxEchange::find($id);
            if (!$tauxEchange) {
                return $this->responseJson(false, 'Taux de change non trouvé.', null, 404);
            }

            // Mettre à jour le taux de change
            $tauxEchange->update($validated);

            return $this->responseJson(true, 'Taux de change mis à jour avec succès.', $tauxEchange);
        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Erreur de validation.', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la mise à jour du taux de change.', $e->getMessage(), 500);
        }
    }
}
