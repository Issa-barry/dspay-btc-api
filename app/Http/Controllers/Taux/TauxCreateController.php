<?php

namespace App\Http\Controllers\Taux;

use App\Http\Controllers\Controller;
use App\Models\TauxEchange;
use App\Models\Devise;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class TauxCreateController extends Controller
{
    use JsonResponseTrait;

    /**
     * Créer un nouveau taux de change avec IDs.
     */
    public function createById(Request $request)
    {
        try {
            // Validation (taux ENTIER strictement positif)
            $validated = $request->validate([
                'devise_source_id' => ['required', 'integer', 'exists:devises,id'],
                'devise_cible_id'  => ['required', 'integer', 'exists:devises,id'],
                'taux'             => ['required', 'integer', 'min:1'],
            ]);

            // Devises différentes
            if ((int)$validated['devise_source_id'] === (int)$validated['devise_cible_id']) {
                return $this->responseJson(false, 'Les devises source et cible doivent être différentes.', null, 400);
            }

            // Un taux existe-t-il déjà dans un sens ou l’autre ?
            $existingTaux = TauxEchange::where(function ($q) use ($validated) {
                $q->where('devise_source_id', $validated['devise_source_id'])
                  ->where('devise_cible_id',  $validated['devise_cible_id']);
            })->orWhere(function ($q) use ($validated) {
                $q->where('devise_source_id', $validated['devise_cible_id'])
                  ->where('devise_cible_id',  $validated['devise_source_id']);
            })->first();

            if ($existingTaux) {
                return $this->responseJson(false, 'Un taux de change entre ces deux devises existe déjà.', null, 409);
            }

            // Création
            $tauxEchange = TauxEchange::create([
                'devise_source_id' => (int) $validated['devise_source_id'],
                'devise_cible_id'  => (int) $validated['devise_cible_id'],
                'taux'             => (int) $validated['taux'],
            ]);

            return $this->responseJson(true, 'Taux de change créé avec succès.', $tauxEchange, 201);

        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Erreur de validation.', $e->errors(), 422);
        } catch (QueryException $e) {
            // Conflit d’unicité (même paire)
            if ($e->getCode() === '23000') {
                return $this->responseJson(false, 'Un taux de change pour cette paire existe déjà.', null, 409);
            }
            return $this->responseJson(false, 'Erreur base de données.', $e->getMessage(), 500);
        } catch (\Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la création du taux de change.', $e->getMessage(), 500);
        }
    }

    /**
     * Créer un nouveau taux de change avec noms des devises.
     */
    public function storeByName(Request $request)
    {
        try {
            // Validation (taux ENTIER strictement positif)
            $validated = $request->validate([
                'devise_source' => ['required', 'string', 'exists:devises,nom'],
                'devise_cible'  => ['required', 'string', 'exists:devises,nom'],
                'taux'          => ['required', 'integer', 'min:1'],
            ]);

            // Récupération des devises
            $deviseSource = Devise::where('nom', $validated['devise_source'])->first();
            $deviseCible  = Devise::where('nom', $validated['devise_cible'])->first();

            if (!$deviseSource || !$deviseCible) {
                return $this->responseJson(false, 'Une ou plusieurs devises ne sont pas valides.', null, 400);
            }

            // Devises différentes
            if ($deviseSource->id === $deviseCible->id) {
                return $this->responseJson(false, 'Les devises source et cible doivent être différentes.', null, 400);
            }

            // Un taux existe-t-il déjà dans un sens ou l’autre ?
            $existingTaux = TauxEchange::where(function ($q) use ($deviseSource, $deviseCible) {
                $q->where('devise_source_id', $deviseSource->id)
                  ->where('devise_cible_id',  $deviseCible->id);
            })->orWhere(function ($q) use ($deviseSource, $deviseCible) {
                $q->where('devise_source_id', $deviseCible->id)
                  ->where('devise_cible_id',  $deviseSource->id);
            })->first();

            if ($existingTaux) {
                return $this->responseJson(false, 'Un taux de change entre ces deux devises existe déjà.', null, 409);
            }

            // Création
            $tauxEchange = TauxEchange::create([
                'devise_source_id' => (int) $deviseSource->id,
                'devise_cible_id'  => (int) $deviseCible->id,
                'taux'             => (int) $validated['taux'],
            ]);

            return $this->responseJson(true, 'Taux de change créé avec succès.', $tauxEchange, 201);

        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Erreur de validation.', $e->errors(), 422);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return $this->responseJson(false, 'Un taux de change pour cette paire existe déjà.', null, 409);
            }
            return $this->responseJson(false, 'Erreur base de données.', $e->getMessage(), 500);
        } catch (\Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la création du taux de change.', $e->getMessage(), 500);
        }
    }
}
