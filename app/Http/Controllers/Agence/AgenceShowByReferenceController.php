<?php

namespace App\Http\Controllers\Agence;

use App\Http\Controllers\Controller;
use App\Models\Agence;
use App\Traits\JsonResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Throwable;

class AgenceShowByReferenceController extends Controller
{
    use JsonResponseTrait;

    /**
     * Récupère une agence par sa référence unique.
     */
    public function getByReference($reference)
    {
        try {
            // Recherche stricte par référence
            $agence = Agence::where('reference', $reference)->firstOrFail();

            return $this->responseJson(true, 'Agence trouvée.', $agence);

        } catch (ModelNotFoundException $e) {
            return $this->responseJson(false, "Aucune agence trouvée avec la référence : $reference", null, 404);

        } catch (QueryException $e) {
            return $this->responseJson(false, 'Erreur de base de données.', $e->getMessage(), 500);

        } catch (Throwable $e) {
            return $this->responseJson(false, 'Une erreur interne est survenue.', $e->getMessage(), 500);
        }
    }
}
