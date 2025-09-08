<?php

namespace App\Http\Controllers\Devises;

use App\Http\Controllers\Controller;
use App\Models\Devise;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviseUpdateController extends Controller
{
    /**
     * Mettre à jour une devise
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateById(Request $request, $id)
    {
        try {
            $devise = Devise::find($id);

            if (!$devise) {
                return response()->json([
                    'success' => false,
                    'message' => 'Devise non trouvée.'
                ], 404);
            }

            // Validation des champs, en ignorant l'ID actuel pour l'unicité du tag
            $validated = $request->validate([
                'nom' => 'sometimes|required|string|max:255',
                'tag' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:10',
                    Rule::unique('devises', 'tag')->ignore($id), // Ignore l'ID actuel lors de la validation unique
                ],
            ]);

            // Mise à jour de la devise
            $devise->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Devise mise à jour avec succès.',
                'data' => $devise
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la mise à jour de la devise.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
