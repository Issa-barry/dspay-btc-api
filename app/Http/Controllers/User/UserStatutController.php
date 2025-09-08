<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserStatutController extends Controller
{
    use JsonResponseTrait;

    /**
     * Met à jour le statut d'un utilisateur (ex: attente → active, bloque).
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatut(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return $this->responseJson(false, 'Utilisateur non trouvé.', null, 404);
            }

            $validated = $request->validate([
                'statut' => 'required|in:active,attente,bloque,archive',
            ]);

            $user->statut = $validated['statut'];
            $user->save();

            return $this->responseJson(true, "Statut de l'utilisateur mis à jour avec succès.", $user);
        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Erreur de validation.', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->responseJson(false, 'Une erreur est survenue.', $e->getMessage(), 500);
        }
    }
}
