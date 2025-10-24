<?php

namespace App\Http\Controllers\Agence;

use App\Http\Controllers\Controller;
use App\Models\Agence;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

class AgenceUpdateByReferenceController extends Controller
{
    use JsonResponseTrait;

    /**
     * PUT /api/v1/agences/reference/{reference}
     */
    public function updateReference(Request $request, string $reference)
    {
        try {
            // 1️⃣ Trouver l'agence par sa référence
            $agence = Agence::where('reference', $reference)->firstOrFail();

            // 2️⃣ Validation des champs à mettre à jour
            $validated = $request->validate([
                'nom'      => ['sometimes', 'required', 'string', 'max:255'],
                'phone'    => ['sometimes', 'required', 'string', 'max:50', Rule::unique('agences', 'phone')->ignore($agence->id)],
                'email'    => ['sometimes', 'required', 'email', 'max:255', Rule::unique('agences', 'email')->ignore($agence->id)],
                'statut'   => ['sometimes', 'required', Rule::in(['active', 'attente', 'bloque', 'archive'])],
                'pays'     => ['sometimes', 'required', 'string', 'max:255'],
                'ville'    => ['sometimes', 'required', 'string', 'max:255'],
                'quartier' => ['sometimes', 'required', 'string', 'max:255'],
            ]);

            // 3️⃣ Mise à jour de l'agence
            $agence->update($validated);

            return $this->responseJson(true, 'Agence mise à jour avec succès.', $agence->fresh());

        } catch (ModelNotFoundException $e) {
            return $this->responseJson(false, 'Aucune agence trouvée avec cette référence.', null, 404);

        } catch (QueryException $e) {
            return $this->responseJson(false, 'Erreur de base de données.', $e->getMessage(), 500);

        } catch (Throwable $e) {
            return $this->responseJson(false, 'Une erreur interne est survenue lors de la mise à jour.', $e->getMessage(), 500);
        }
    }
}
