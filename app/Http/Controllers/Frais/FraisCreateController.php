<?php

namespace App\Http\Controllers\Frais;

use App\Http\Controllers\Controller;
use App\Models\Frais;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Traits\JsonResponseTrait;

class FraisCreateController extends Controller
{
    use JsonResponseTrait;

    /**
     * Ajouter un nouveau frais sans doublon et avec un contrôle qualité des données.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        try {
            $validated = $this->validateFrais($request);

            if ($this->fraisExists($validated['nom'], $validated['type'])) {
                return $this->responseJson(false, 'Un frais avec ce nom et type existe déjà.', null, 409);
            }

            // Normaliser le format du nom : première lettre en majuscule, reste en minuscule
            $validated['nom'] = ucfirst(strtolower($validated['nom']));

            // Vérification qualité des données
            if (!is_numeric($validated['valeur']) || $validated['valeur'] < 0) {
                return $this->responseJson(false, 'La valeur du frais doit être un nombre positif ou zéro.', null, 422);
            }

            if (!is_string($validated['nom']) || strlen($validated['nom']) < 3) {
                return $this->responseJson(false, 'Le nom du frais doit contenir au moins 3 caractères.', null, 422);
            }

            $frais = Frais::create($validated);
            return $this->responseJson(true, 'Frais créé avec succès.', $frais, 201);
        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Erreur de validation.', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la création du frais.', $e->getMessage(), 500);
        }
    }

    /**
     * Valider les données du frais avec des contrôles de qualité.
     *
     * @param Request $request
     * @return array
     */
    private function validateFrais(Request $request)
    {
        return $request->validate([
            'nom' => 'required|string|min:3|max:255',
            'type' => 'required|in:fixe,pourcentage',
            'valeur' => 'required|numeric|min:0',
            'montant_min' => 'required|numeric|min:0',
            'montant_max' => 'numeric',
        ]);
    }

    /**
     * Vérifier si un frais existe déjà.
     *
     * @param string $nom
     * @param string $type
     * @return bool
     */
    private function fraisExists($nom, $type)
    {
        return Frais::where('nom', $nom)->where('type', $type)->exists();
    }
}
