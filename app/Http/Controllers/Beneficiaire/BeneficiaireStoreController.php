<?php

namespace App\Http\Controllers\Beneficiaire;

use App\Http\Controllers\Controller;
use App\Models\Beneficiaire;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BeneficiaireStoreController extends Controller
{
    use JsonResponseTrait;

    public function store(Request $r)
    {
        try {
            $validated = $r->validate([
                'nom'    => ['required', 'string', 'max:100'],
                'prenom' => ['required', 'string', 'max:100'],
                'phone'  => [
                    'required', 'string', 'min:6', 'max:30',
                    Rule::unique('beneficiaires', 'phone')
                        ->where(fn($q) => $q->where('user_id', $r->user()->id)),
                ],
            ]);

            $benef = Beneficiaire::create([
                'user_id' => $r->user()->id,
                'nom'     => $validated['nom'],
                'prenom'  => $validated['prenom'],
                'phone'   => $validated['phone'],
            ]);

            return $this->responseJson(true, 'Bénéficiaire créé avec succès.', $benef, 201);

        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Échec de la validation des données.', $e->errors(), 422);

        } catch (\Throwable $e) {
            return $this->responseJson(false, 'Une erreur est survenue lors de la création.', $e->getMessage(), 500);
        }
    }
}
