<?php

namespace App\Http\Controllers\Agence;

use App\Http\Controllers\Controller;
use App\Models\Adresse;
use App\Models\Agence;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgenceCreateController extends Controller
{
    use JsonResponseTrait;

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nom_agence'            => 'required|string|max:255',
                'phone'                 => 'required|string|max:20|unique:agences,phone',
                'email'                 => 'required|email|max:255|unique:agences,email',
                'date_creation'         => 'nullable|date',
                // plus de responsable_reference

                'adresse'                    => 'required|array',
                'adresse.pays'               => 'required|string|max:255',
                'adresse.adresse'            => 'required|string|max:255',
                'adresse.complement_adresse' => 'nullable|string|max:255',
                'adresse.ville'              => 'required|string|max:255',
                'adresse.code_postal'        => 'required|string|max:20',
            ]);

            $agence = DB::transaction(function () use ($validated) {
                // 1) Adresse
                $adresse = Adresse::create($validated['adresse']);

                // 2) Agence (sans responsable_id)
                $payload = Arr::except($validated, ['adresse']);
                $payload['adresse_id'] = $adresse->id;
                $payload['statut']     = $payload['statut'] ?? 'active';

                /** @var \App\Models\Agence $agence */
                $agence = Agence::create($payload);

                return $agence->load('adresse');
            });

            return $this->responseJson(true, 'Agence créée avec succès.', $agence, 201);
        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Échec de la validation des données.', $e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->responseJson(false, 'Une erreur interne est survenue lors de la création de l\'agence.', $e->getMessage(), 500);
        }
    }
}
