<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Exception;
use Illuminate\Support\Facades\Log;

class UserAffecterAgenceController extends Controller
{
    use JsonResponseTrait;

    /**
     * Affecter un utilisateur à une agence.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function affecterAgence(Request $request, $userId)
    {
        try {
            // Validation des données entrantes
            $validated = $request->validate([
                'agence_id' => 'required|exists:agences,id'
            ]);

            // Recherche de l'utilisateur
            $user = User::where('id', $userId)->firstOrFail();

            // Vérifier si l'utilisateur est déjà affecté à cette agence
            if ($user->agence_id === $validated['agence_id']) {
                return $this->responseJson(false, 'Utilisateur déjà affecté à cette agence.', null, 400);
            }

            // Mise à jour de l'affectation
            $user->update(['agence_id' => $validated['agence_id']]);

            return $this->responseJson(true, 'Utilisateur affecté à l\'agence avec succès.', $user->load('agence'), 200);
        } catch (ValidationException $e) {
            Log::warning('Erreur de validation lors de l\'affectation : ' . json_encode($e->errors()));
            return $this->responseJson(false, 'Erreur de validation.', $e->errors(), 422);
        } catch (QueryException $e) {
            Log::error('Erreur SQL lors de l\'affectation : ' . $e->getMessage());
            return $this->responseJson(false, 'Erreur de base de données.', null, 500);
        } catch (Exception $e) {
            Log::error('Erreur inattendue lors de l\'affectation : ' . $e->getMessage());
            return $this->responseJson(false, 'Une erreur interne est survenue.', null, 500);
        }
    }


    /**
 * Affecter un utilisateur à une agence en utilisant la référence de l'agence.
 *
 * @param \Illuminate\Http\Request $request
 * @param int $userId
 * @return \Illuminate\Http\JsonResponse
 */
public function affecterParReferenceAgence(Request $request, $userId)
{
    try {
        // Validation de la référence d'agence
        $validated = $request->validate([
            'reference' => 'required|string|exists:agences,reference'
        ]);

        // Recherche de l'agence par référence
        $agence = \App\Models\Agence::where('reference', $validated['reference'])->first();

        if (!$agence) {
            return $this->responseJson(false, 'Agence non trouvée avec cette référence.', null, 404);
        }

        // Recherche de l'utilisateur
        $user = User::where('id', $userId)->firstOrFail();

        // Vérifier si l'utilisateur est déjà affecté à cette agence
        if ($user->agence_id === $agence->id) {
            return $this->responseJson(false, 'Utilisateur déjà affecté à cette agence.', null, 400);
        }

        // Mise à jour de l'affectation
        $user->update(['agence_id' => $agence->id]);

        return $this->responseJson(true, 'Utilisateur affecté à l\'agence avec succès.', $user->load('agence'), 200);
    } catch (ValidationException $e) {
        Log::warning('Erreur de validation lors de l\'affectation : ' . json_encode($e->errors()));
        return $this->responseJson(false, 'Erreur de validation.', $e->errors(), 422);
    } catch (QueryException $e) {
        Log::error('Erreur SQL lors de l\'affectation : ' . $e->getMessage());
        return $this->responseJson(false, 'Erreur de base de données.', null, 500);
    } catch (Exception $e) {
        Log::error('Erreur inattendue lors de l\'affectation : ' . $e->getMessage());
        return $this->responseJson(false, 'Une erreur interne est survenue.', null, 500);
    }
}

}
