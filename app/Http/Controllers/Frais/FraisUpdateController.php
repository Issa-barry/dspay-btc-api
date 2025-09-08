<?php

namespace App\Http\Controllers\Frais;

use App\Http\Controllers\Controller;
use App\Models\Frais;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Traits\JsonResponseTrait;

class FraisUpdateController extends Controller
{
    use JsonResponseTrait;

    /**
     * Mettre à jour un frais existant sans créer de doublon et avec un contrôle qualité des données.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function updateById(Request $request, $id)
    {
        try {
            $validated = $this->validateFrais($request, true);
            $frais = Frais::findOrFail($id);

            // Vérifier si un autre frais avec le même nom et type existe déjà (exclure l'ID en cours de mise à jour)
            if ($this->fraisExists($validated['nom'], $validated['type'], $id) && ($frais->nom !== $validated['nom'] || $frais->type !== $validated['type'])) {
                return $this->responseJson(false, 'Un autre frais avec ce nom et type existe déjà.', null, 409);
            }

            // Vérification supplémentaire sur la qualité des données
            if (!is_numeric($validated['valeur']) || $validated['valeur'] < 0) {
                return $this->responseJson(false, 'La valeur du frais doit être un nombre positif ou zéro.', null, 422);
            }

            if (!is_string($validated['nom']) || strlen($validated['nom']) < 3) {
                return $this->responseJson(false, 'Le nom du frais doit contenir au moins 3 caractères.', null, 422);
            }

            // Normaliser le format du nom : première lettre en majuscule, reste en minuscule
            $validated['nom'] = ucfirst(strtolower($validated['nom']));

            $frais->update($validated);
            return $this->responseJson(true, 'Frais mis à jour avec succès.', $frais);
        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Erreur de validation.', $e->errors(), 422);
        } catch (ModelNotFoundException $e) {
            return $this->responseJson(false, 'Frais non trouvé.', null, 404);
        } catch (\Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la mise à jour du frais.', $e->getMessage(), 500);
        }
    }

    /**
     * Valider les données du frais avec des contrôles de qualité.
     *
     * @param Request $request
     * @param bool $isUpdate
     * @return array
     */
    private function validateFrais(Request $request, $isUpdate = false)
    {
        return $request->validate([
            'nom' => $isUpdate ? 'sometimes|string|min:3|max:255' : 'required|string|min:3|max:255',
            'type' => $isUpdate ? 'sometimes|in:fixe,pourcentage' : 'required|in:fixe,pourcentage',
            'valeur' => $isUpdate ? 'sometimes|numeric|min:0' : 'required|numeric|min:0',
        ]);
    }

    /**
     * Vérifier si un frais existe déjà (exclure l'ID en cours de mise à jour).
     *
     * @param string $nom
     * @param string $type
     * @param int|null $excludeId
     * @return bool
     */
    private function fraisExists($nom, $type, $excludeId = null)
    {
        $query = Frais::where('nom', $nom)->where('type', $type);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }
}
