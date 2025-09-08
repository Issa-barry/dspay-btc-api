<?php

namespace App\Http\Controllers\Factures;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class FactureController extends Controller
{
    /**
     * Fonction pour centraliser les réponses JSON.
     */
    protected function responseJson($success, $message, $data = null, $statusCode = 200)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Afficher toutes les factures.
     */
    public function index()
    {
        try {
            $factures = Facture::with('payments', 'transfert')->get();

            foreach ($factures as $facture) {
                if ($facture->transfert->statut === 'en_cours') {
                    $facture->transfert->makeHidden('code');
                }
            }

            return $this->responseJson(true, 'Liste des factures récupérée avec succès.', $factures);
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la récupération des factures.', $e->getMessage(), 500);
        }
    }

    /**
     * Afficher les détails d'une facture spécifique.
     */
    public function show($id)
    {
        try {
            $facture = Facture::with('payments', 'transfert')->find($id);

            if (!$facture) {
                return $this->responseJson(false, 'Facture non trouvée.', null, 404);
            }

            $facture->transfert->makeHidden('code');
            return $this->responseJson(true, 'Facture récupérée avec succès.', $facture);
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la récupération de la facture.', $e->getMessage(), 500);
        }
    }

    /**
     * Effectuer un paiement sur une facture.
     */
    public function payerFacture(Request $request, $factureId)
{
    // Validation des données d'entrée
    $validated = Validator::make($request->all(), [
        'type' => 'required|in:virement,cash', // Type de paiement
        'montant' => 'required|numeric|min:0', // Montant payé
    ]);

    if ($validated->fails()) {
        return $this->responseJson(false, 'Validation échouée.', $validated->errors(), 422);
    }

    try {
        // Récupérer la facture
        $facture = Facture::findOrFail($factureId);

        // Vérifier si le montant dû est déjà 0
        if ($facture->montant_du == 0) {
            return $this->responseJson(false, 'Cette facture est déjà payée et ne peut plus recevoir de paiements.', null, 400);
        }

        // Vérifier que le montant payé ne dépasse pas le montant dû
        if ($request->montant > $facture->montant_du) {
            return $this->responseJson(false, 'Le montant payé ne peut pas être supérieur au montant dû.', null, 400);
        }

        // Créer le paiement
        $payment = Payment::create([
            'facture_id' => $facture->id,
            'type' => $request->type,
            'montant' => $request->montant
        ]);

        // Mettre à jour le montant dû de la facture après paiement
        $facture->updateMontantDu();

        // Mettre à jour le statut de la facture
        if ($facture->montant_du == 0) {
            $facture->statut = 'payé'; // Facture complètement payée
        } else {
            $facture->statut = 'partiel'; // Facture partiellement payée
        }

        $facture->save();

        return $this->responseJson(true, 'Paiement effectué avec succès.', [
            'payment' => $payment,
            'montant_du' => $facture->montant_du
        ], 200);
    } catch (Exception $e) {
        return $this->responseJson(false, 'Erreur lors du paiement de la facture.', $e->getMessage(), 500);
    }
}


}
