<?php

namespace App\Http\Controllers\Agence;

use App\Http\Controllers\Controller;
use App\Models\Adresse;
use App\Models\Agence;
use App\Models\User;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AgenceCreateController extends Controller
{
    use JsonResponseTrait;

    /**
     * Créer une nouvelle agence.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nom_agence' => 'required|string|max:255',
                'phone' => 'required|string|max:20|unique:agences,phone',
                'email' => 'required|email|max:255|unique:agences,email',
                'date_creation' => 'nullable|date',
                'adresse' => 'required|array',
                'adresse.pays' => 'required|string|max:255',
                'adresse.adresse' => 'required|string|max:255',
                'adresse.complement_adresse' => 'nullable|string|max:255',
                'adresse.ville' => 'required|string|max:255',
                'adresse.code_postal' => 'required|string|max:20',
                'responsable_reference' => 'required|string|exists:users,reference'
            ]);

            $responsable = User::where('reference', $validated['responsable_reference'])->first();

            if (!$responsable) {
                return $this->responseJson(false, 'Responsable introuvable.', null, 404);
            }

            // Vérification stricte du rôle (respecte la casse : "Responsable agence")
            if ($responsable->role !== 'Responsable agence') {
                return $this->responseJson(false, 'L\'utilisateur désigné n\'a pas le rôle "Responsable agence".', null, 422);
            }

            // Création de l'adresse
            $adresse = Adresse::create($validated['adresse']);

            // Préparation des données agence
            $agenceData = array_merge(
                \Arr::except($validated, ['adresse', 'responsable_reference']),
                [
                    'adresse_id' => $adresse->id,
                    'responsable_id' => $responsable->id
                ]
            );

            // Création de l'agence
            $agence = Agence::create($agenceData);

            // Affecter l'agence au responsable
            $responsable->agence_id = $agence->id;
            $responsable->save();

            return $this->responseJson(true, 'Agence créée avec succès.', $agence->load('adresse', 'responsable'), 201);
        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Échec de la validation des données.', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->responseJson(false, 'Une erreur interne est survenue lors de la création de l\'agence.', $e->getMessage(), 500);
        }
    }
}
