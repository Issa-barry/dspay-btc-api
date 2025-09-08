<?php

namespace App\Http\Controllers\Taux;

use App\Http\Controllers\Controller;
use App\Models\TauxEchange;
use App\Models\Devise;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TauxCreateController extends Controller
{
    use JsonResponseTrait;

    /**
     * Créer un nouveau taux de change avec IDs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createById(Request $request)
    {
        try {
            // Validation des données d'entrée
            $validated = $request->validate([
                'devise_source_id' => 'required|integer|exists:devises,id',
                'devise_cible_id' => 'required|integer|exists:devises,id',
                'taux' => 'required|numeric|min:0',
            ]);

            // Vérifier que les devises sont différentes
            if ($validated['devise_source_id'] === $validated['devise_cible_id']) {
                return $this->responseJson(false, 'Les devises source et cible doivent être différentes.', null, 400);
            }

            // Vérifier si un taux existe déjà dans les deux sens
            $existingTaux = TauxEchange::where(function ($query) use ($validated) {
                $query->where('devise_source_id', $validated['devise_source_id'])
                      ->where('devise_cible_id', $validated['devise_cible_id']);
            })->orWhere(function ($query) use ($validated) {
                $query->where('devise_source_id', $validated['devise_cible_id'])
                      ->where('devise_cible_id', $validated['devise_source_id']);
            })->first();

            if ($existingTaux) {
                return $this->responseJson(false, 'Un taux de change entre ces deux devises existe déjà.', null, 409);
            }

            // Création du taux de change
            $tauxEchange = TauxEchange::create($validated);

            return $this->responseJson(true, 'Taux de change créé avec succès.', $tauxEchange, 201);
        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Erreur de validation.', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la création du taux de change.', $e->getMessage(), 500);
        }
    }

    /**
     * Créer un nouveau taux de change avec noms des devises.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeByName(Request $request)
    {
        try {
            // Validation des données d'entrée
            $validated = $request->validate([
                'devise_source' => 'required|string|exists:devises,nom',
                'devise_cible' => 'required|string|exists:devises,nom',
                'taux' => 'required|numeric|min:0',
            ]);

            // Récupération des devises
            $deviseSource = Devise::where('nom', $validated['devise_source'])->first();
            $deviseCible = Devise::where('nom', $validated['devise_cible'])->first();

            if (!$deviseSource || !$deviseCible) {
                return $this->responseJson(false, 'Une ou plusieurs devises ne sont pas valides.', null, 400);
            }

            // Vérifier que les devises sont différentes
            if ($deviseSource->id === $deviseCible->id) {
                return $this->responseJson(false, 'Les devises source et cible doivent être différentes.', null, 400);
            }

            // Vérifier si un taux existe déjà dans les deux sens
            $existingTaux = TauxEchange::where(function ($query) use ($deviseSource, $deviseCible) {
                $query->where('devise_source_id', $deviseSource->id)
                      ->where('devise_cible_id', $deviseCible->id);
            })->orWhere(function ($query) use ($deviseSource, $deviseCible) {
                $query->where('devise_source_id', $deviseCible->id)
                      ->where('devise_cible_id', $deviseSource->id);
            })->first();

            if ($existingTaux) {
                return $this->responseJson(false, 'Un taux de change entre ces deux devises existe déjà.', null, 409);
            }

            // Création du taux de change
            $tauxEchange = TauxEchange::create([
                'devise_source_id' => $deviseSource->id,
                'devise_cible_id' => $deviseCible->id,
                'taux' => $validated['taux'],
            ]);

            return $this->responseJson(true, 'Taux de change créé avec succès.', $tauxEchange, 201);
        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Erreur de validation.', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la création du taux de change.', $e->getMessage(), 500);
        }
    }
}
