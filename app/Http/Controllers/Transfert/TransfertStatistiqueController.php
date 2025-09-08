<?php

namespace App\Http\Controllers\Transfert;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Transfert;

class TransfertStatistiqueController extends Controller
{
    /**
     * Récupère les statistiques des transferts pour une agence donnée.
     *
     * @param int $agenceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSommeTransfertsParAgence($agenceId)
    {
        try {
            // Vérifie les transferts dont l'agent appartient à l'agence
            $somme = Transfert::whereHas('agent', function ($query) use ($agenceId) {
                    $query->where('agence_id', $agenceId);
                })
                ->where('statut', '!=', 'retiré') // exclure les retirés (facultatif)
                ->selectRaw('
                    SUM(montant_expediteur) as total_envoye,
                    SUM(montant_receveur) as total_recu,
                    SUM(total) as total_general,
                    COUNT(*) as nb_transferts
                ')
                ->first();

            return response()->json([
                'success' => true,
                'data' => $somme
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les statistiques globales de tous les transferts.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistiquesGlobales()
    {
        try {
            $stats = Transfert::where('statut', '!=', 'retiré') // facultatif : ignorer les transferts retirés
                ->selectRaw('
                    SUM(montant_expediteur) as total_envoye,
                    SUM(montant_receveur) as total_recu,
                    SUM(total) as total_general,
                    COUNT(*) as nb_transferts
                ')
                ->first();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul global.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
