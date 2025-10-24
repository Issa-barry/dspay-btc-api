<?php

namespace App\Http\Controllers\Agence;

use App\Http\Controllers\Controller;
use App\Models\Agence;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Throwable;

class AgenceCreateController extends Controller
{
    use JsonResponseTrait;

    public function store(Request $request)
    {
        try {
            // === Validation des données ===
            $validated = $request->validate([
                'nom'      => ['required', 'string', 'max:255'],
                'phone'    => ['required', 'string', 'max:50', 'unique:agences,phone'],
                'email'    => ['required', 'email', 'max:255', 'unique:agences,email'],
                'statut'   => ['nullable', 'in:active,attente,bloque,archive'],
                'pays'     => ['required', 'string', 'max:255'],
                'ville'    => ['required', 'string', 'max:255'],
                'quartier' => ['required', 'string', 'max:255'],
            ]);

            // === Création de l’agence ===
            $agence = Agence::create($validated);

            return $this->responseJson(
                true,
                'Agence créée avec succès.',
                $agence->fresh(),
                201
            );

        } catch (ValidationException $e) {
            // Erreurs de validation → code 422
            return $this->responseJson(
                false,
                'Échec de validation des données.',
                $e->errors(),
                422
            );

        } catch (QueryException $e) {
            // Erreurs SQL (doublons, etc.) → code 500
            return $this->responseJson(
                false,
                'Erreur de base de données.',
                $e->getMessage(),
                500
            );

        } catch (Throwable $e) {
            // Erreurs générales → code 500
            return $this->responseJson(
                false,
                'Une erreur interne est survenue lors de la création de l’agence.',
                $e->getMessage(),
                500
            );
        }
    }
}
