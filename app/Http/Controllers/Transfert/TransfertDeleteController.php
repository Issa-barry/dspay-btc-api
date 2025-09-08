<?php

namespace App\Http\Controllers\Transfert;

use App\Http\Controllers\Controller;
use App\Models\Transfert;
use App\Traits\JsonResponseTrait;
use Exception;
use Illuminate\Http\Request;

class TransfertDeleteController extends Controller
{
    use JsonResponseTrait;

    /**
     * Supprimer un transfert par son ID.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteById($id)
    {
        try {
            $transfert = Transfert::find($id);

            if (!$transfert) {
                return $this->responseJson(false, 'Transfert non trouvé.', null, 404);
            }

            // Supprimer la facture associée avant de supprimer le transfert
            if ($transfert->facture) {
                $transfert->facture->delete();
            }

            $transfert->delete();

            return $this->responseJson(true, 'Transfert supprimé avec succès.');
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la suppression.', $e->getMessage(), 500);
        }
    }

    /**
     * Supprimer un transfert par son code.
     *
     * @param  string  $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteByCode($code)
    {
        try {
            $transfert = Transfert::where('code', $code)->first();

            if (!$transfert) {
                return $this->responseJson(false, 'Transfert non trouvé.', null, 404);
            }

            // Supprimer la facture associée avant de supprimer le transfert
            if ($transfert->facture) {
                $transfert->facture->delete();
            }
            $transfert->delete();

            return $this->responseJson(true, 'Transfert et facture supprimer avec succès.');
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la suppression.', $e->getMessage(), 500);
        }
    }
}
