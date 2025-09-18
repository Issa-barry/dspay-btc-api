<?php
// app/Http/Controllers/Beneficiaire/BeneficiaireUpdateController.php

namespace App\Http\Controllers\Beneficiaire;

use App\Http\Controllers\Controller;
use App\Models\Beneficiaire;
use App\Traits\JsonResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BeneficiaireUpdateController extends Controller
{
    use JsonResponseTrait;

    public function updateById(Request $r, int $id)
    {
        try {
            // safe même si $r->user() == null, et évite l’avertissement Intelephense
            $userId = $r->user()?->id ?? Auth::id();
            if (!$userId) {
                return $this->responseJson(false, 'Non authentifié.', null, 401);
            }

            // Bénéficiaire appartenant à l’utilisateur courant
            $benef = Beneficiaire::where('user_id', $userId)->findOrFail($id);

            // Validation partielle (PATCH) + unicité du phone scoping user + ignore courant
            $validated = $r->validate([
                'nom'    => ['sometimes', 'required', 'string', 'max:100'],
                'prenom' => ['sometimes', 'required', 'string', 'max:100'],
                'phone'  => [
                    'sometimes', 'required', 'string', 'min:6', 'max:30',
                    Rule::unique('beneficiaires', 'phone')
                        ->where(fn($q) => $q->where('user_id', $userId))
                        ->ignore($benef->id),
                ],
            ]);

            $benef->fill($validated)->save();

            return $this->responseJson(true, 'Bénéficiaire mis à jour.', $benef);

        } catch (ModelNotFoundException $e) {
            return $this->responseJson(false, 'Bénéficiaire introuvable.', null, 404);

        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Échec de la validation des données.', $e->errors(), 422);

        } catch (\Throwable $e) {
            return $this->responseJson(false, 'Une erreur est survenue lors de la mise à jour.', $e->getMessage(), 500);
        }
    }
}
