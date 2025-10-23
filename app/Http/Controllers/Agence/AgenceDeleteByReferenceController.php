<?php

namespace App\Http\Controllers\Agence;

use App\Http\Controllers\Controller;
use App\Models\Agence;
use App\Traits\JsonResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Throwable;

class AgenceDeleteByReferenceController extends Controller
{
    use JsonResponseTrait;

    /**
     * DELETE /api/v1/agences/reference/{reference}
     */
    public function deleteByReference(string $reference)
    {
        try {
            $agence = Agence::where('reference', $reference)->firstOrFail();

            $agence->delete(); // hard delete (pas de SoftDeletes)

            return $this->responseJson(true, 'Agence supprimée avec succès.', [
                'reference' => $reference
            ]);

            // Variante 204 sans corps :
            // return response()->noContent();

        } catch (ModelNotFoundException $e) {
            return $this->responseJson(false, 'Aucune agence trouvée avec cette référence.', null, 404);

        } catch (QueryException $e) {
            return $this->responseJson(false, 'Erreur de base de données.', $e->getMessage(), 500);

        } catch (Throwable $e) {
            return $this->responseJson(false, 'Une erreur interne est survenue lors de la suppression.', $e->getMessage(), 500);
        }
    }
}
