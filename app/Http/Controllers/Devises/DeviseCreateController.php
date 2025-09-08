<?php

namespace App\Http\Controllers\Devises;

use App\Http\Controllers\Controller;
use App\Models\Devise;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class DeviseCreateController extends Controller
{
    use JsonResponseTrait; 

    /**
     * Créer une devise
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nom' => 'required|string|max:255|unique:devises',
                'tag' => 'required|string|max:10|unique:devises',
            ]);

            // Vérification si la devise existe déjà (évite une requête inutile)
            if (Devise::where('nom', $validated['nom'])->orWhere('tag', $validated['tag'])->exists()) {
                return $this->responseJson(false, 'Cette devise existe déjà.', null, 400);
            }

            $devise = Devise::create($validated);

            return $this->responseJson(true, 'Devise créée avec succès.', $devise, 201);
        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Erreur de validation.', $e->errors(), 422);
        } catch (Exception $e) {
            return $this->responseJson(false, 'Une erreur est survenue lors de la création de la devise.', $e->getMessage(), 500);
        }
    }
}
