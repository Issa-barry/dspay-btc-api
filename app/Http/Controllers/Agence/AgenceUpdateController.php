<?php

namespace App\Http\Controllers\Agence;

use App\Http\Controllers\Controller;
use App\Models\Agence;
use App\Models\User;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AgenceUpdateController extends Controller
{
    use JsonResponseTrait;

    /**
     * Mettre à jour une agence.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateById(Request $request, $id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->responseJson(false, 'ID invalide.', null, 400);
            }

            $agence = Agence::find($id);
            if (!$agence) {
                return $this->responseJson(false, 'Agence non trouvée.', null, 404);
            }

            $validated = $request->validate([
                'reference' => 'string|min:5|max:6|unique:agences,reference,' . $id,
                'nom_agence' => 'string|max:255',
                'phone' => 'string|max:20|unique:agences,phone,' . $id,
                'email' => 'email|max:255|unique:agences,email,' . $id,
                'statut' => 'in:active,attente,bloque,archive',
                'date_creation' => 'date',
                'responsable_reference' => 'nullable|string|exists:users,reference',
                'adresse' => 'array',
                'adresse.pays' => 'string|max:255',
                'adresse.adresse' => 'string|max:255',
                'adresse.complement_adresse' => 'nullable|string|max:255',
                'adresse.ville' => 'string|max:255',
                'adresse.code_postal' => 'string|max:20',
                'adresse.quartier' => 'nullable|string|max:255',
                'adresse.region' => 'nullable|string|max:255',
            ]);

            // Si une nouvelle référence responsable est fournie
            // Si une nouvelle référence responsable est fournie
            // Si une nouvelle référence responsable est fournie
            if (!empty($validated['responsable_reference'])) {
                $responsable = User::where('reference', $validated['responsable_reference'])->first();

                if (!$responsable) {
                    return $this->responseJson(false, 'Responsable introuvable.', null, 404);
                }

                // Vérifier le rôle
                if (strtolower($responsable->role) !== 'responsable agence') {
                    return $this->responseJson(false, 'L\'utilisateur désigné comme responssable n\'a pas le rôle "Responsable agence".', null, 422);
                }

                // Affecter comme responsable de l’agence
                $agence->responsable_id = $responsable->id;

                // Affecter l’agence à l’utilisateur aussi
                $responsable->agence_id = $agence->id;
                $responsable->save();

                unset($validated['responsable_reference']);
            }



            // Mise à jour de l'adresse
            if (!empty($validated['adresse']) && $agence->adresse) {
                $agence->adresse->update($validated['adresse']);
            }

            // Mise à jour de l’agence
            $agence->update(collect($validated)->except('adresse')->toArray());

            // Sauvegarder en cas de changement de responsable_id
            $agence->save();

            return $this->responseJson(true, 'Agence mise à jour avec succès.', $agence->load('adresse', 'responsable'));
        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Erreur de validation des données.', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->responseJson(false, 'Une erreur interne est survenue lors de la mise à jour.', $e->getMessage(), 500);
        }
    }
}
