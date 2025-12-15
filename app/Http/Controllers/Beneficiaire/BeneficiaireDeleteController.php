<?php

namespace App\Http\Controllers\Beneficiaire;

use App\Http\Controllers\Controller;
use App\Models\Beneficiaire;
use App\Traits\JsonResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class BeneficiaireDeleteController extends Controller
{
    use JsonResponseTrait;

    public function deleteById(Request $r, $id)
    {
        try {
            // 🔐 sécurité : uniquement les bénéficiaires du user connecté
            $benef = Beneficiaire::where('user_id', $r->user()->id)
                ->withTrashed() // ⚠️ important si déjà soft-deleted
                ->findOrFail($id);

            // ❌ suppression définitive
            $benef->forceDelete();

            return $this->responseJson(true, 'Bénéficiaire supprimé définitivement.', null, 200);

        } catch (ModelNotFoundException $e) {
            return $this->responseJson(false, 'Bénéficiaire introuvable.', null, 404);

        } catch (\Throwable $e) {
            return $this->responseJson(
                false,
                'Une erreur est survenue lors de la suppression.',
                $e->getMessage(),
                500
            );
        }
    }
}
