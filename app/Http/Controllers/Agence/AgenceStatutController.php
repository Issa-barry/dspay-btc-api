<?php

namespace App\Http\Controllers\Agence;

use App\Http\Controllers\Controller;
use App\Models\Agence;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AgenceStatutController extends Controller
{
    use JsonResponseTrait;

    /**
     * Met à jour le statut d'une agence (ex: attente → active).
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatut(Request $request, $id)
    {
        try {
            $agence = Agence::find($id);

            if (!$agence) {
                return $this->responseJson(false, 'Agence non trouvée.', null, 404);
            }

            $validated = $request->validate([
                'statut' => 'required|in:active,attente,bloque,archive',
            ]);

            $agence->statut = $validated['statut'];
            $agence->save();

            return $this->responseJson(true, 'Statut de l\'agence mis à jour avec succès.', $agence);
        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Erreur de validation.', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->responseJson(false, 'Une erreur est survenue.', $e->getMessage(), 500);
        }
    }
}
