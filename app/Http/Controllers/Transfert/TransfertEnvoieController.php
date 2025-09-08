<?php

namespace App\Http\Controllers\Transfert;

use App\Http\Controllers\Controller;
use App\Mail\TransfertNotification;
use App\Models\Devise;
use App\Models\Facture;
use App\Models\Frais;
use App\Models\TauxEchange;
use App\Models\Transfert;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Exception;

class TransfertEnvoieController extends Controller
{
    use JsonResponseTrait;

    /**
     * Créer un transfert de devise en utilisant un taux existant et en appliquant les frais spécifiques.
     */
    public function store(Request $request)
    {
        // Validation de la requête
        $validated = $this->validateRequest($request);
        if ($validated->fails()) {
            return $this->responseJson(false, 'Validation échouée.', $validated->errors(), 422);
        }

        try {
            // Récupérer les données nécessaires
            $tauxEchange = TauxEchange::findOrFail($request->taux_echange_id);
            $deviseSource = Devise::findOrFail($tauxEchange->devise_source_id);
            $deviseCible = Devise::findOrFail($tauxEchange->devise_cible_id);

            // Calcul du montant converti
            $montantConverti = $request->montant_expediteur * $tauxEchange->taux;

            // Calcul des frais basés sur le montant
            $frais = $this->calculerFrais($request->montant_expediteur);
            $total = $request->montant_expediteur + $frais;

            // Créer les données du transfert
            $transfertData = $this->mapTransfertData($request, $tauxEchange, $deviseSource, $deviseCible, $montantConverti, $frais, $total);
            $transfertData['agent_id'] = auth('sanctum')->id() ?? auth('api')->id(); // Ajout de l'agent connecté

            // Créer le transfert
            $transfert = Transfert::create($transfertData);

            // Créer la facture associée
            $this->createFacture($transfert);
 
            // Envoyer un email de confirmation
            $this->envoyerEmailConfirmation($transfert);

            // Retourner une réponse JSON avec succès
            return $this->responseJson(true, 'Transfert effectué avec succès.', [
                'transfert' => $transfert,
                'agent' => $transfert->agent ? $transfert->agent->nom_complet : 'Non assigné'
            ], 201);
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la création du transfert.', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Valider les données de la requête.
     */
    private function validateRequest(Request $request)
    {
        return Validator::make($request->all(), [
            'taux_echange_id' => 'required|exists:taux_echanges,id',
            'montant_expediteur' => 'required|numeric|min:1',
            'quartier' => 'nullable|string',
            'receveur_nom_complet' => 'required|string|max:255',
            'receveur_phone' => 'required|string|max:20',
            'expediteur_nom_complet' => 'required|string|max:255',
            'expediteur_phone' => 'required|string|max:20',
            'expediteur_email' => 'required|email|max:255',
        ]);
    }

    /**
     * Calculer les frais du transfert selon les bornes et type.
     */
    private function calculerFrais($montant)
    {
        // Rechercher le frais correspondant au montant du transfert
        $frais = Frais::where('montant_min', '<=', $montant)
                      ->where(function ($query) use ($montant) {
                          $query->where('montant_max', '>=', $montant)
                                ->orWhereNull('montant_max'); // Si null, pas de limite supérieure
                      })
                      ->orderBy('montant_min', 'asc') // Assurer l'ordre croissant
                      ->first();

        // Si aucun frais n'est trouvé, retourner 0
        if (!$frais) {
            return 0;
        }

        // Calculer les frais en fonction du type (fixe ou pourcentage)
        if ($frais->type === 'pourcentage') {
            return max(1, $montant * ($frais->valeur / 100)); // Applique le pourcentage
        }

        return $frais->valeur; // Applique les frais fixes
    }

    /**
     * Mapper les données du transfert.
     */
    private function mapTransfertData($request, $tauxEchange, $deviseSource, $deviseCible, $montantConverti, $frais, $total)
    {
        return [
            'devise_source_id' => $deviseSource->id,
            'devise_cible_id' => $deviseCible->id,
            'montant_expediteur' => $request->montant_expediteur,
            'montant_receveur' => $montantConverti,
            'frais' => $frais,
            'total' => $total,
            'quartier' => $request->quartier,
            'receveur_nom_complet' => $request->receveur_nom_complet,
            'receveur_phone' => $request->receveur_phone,
            'expediteur_nom_complet' => $request->expediteur_nom_complet,
            'expediteur_phone' => $request->expediteur_phone,
            'expediteur_email' => $request->expediteur_email,
            'taux_echange_id' => $tauxEchange->id,
            'statut' => 'en_cours',
            'code' => Transfert::generateUniqueCode(),
        ];
    }

    /**
     * Créer une facture pour un transfert donné.
     */
    private function createFacture(Transfert $transfert)
    {
        Facture::create([
            'transfert_id' => $transfert->id,
            'type' => 'transfert',
            'statut' => 'brouillon',
            'envoye' => false,
            'nom_societe' => 'FELLO',
            'adresse_societe' => '5 allé du Foehn Ostwald 67540, Strasbourg.',
            'phone_societe' => 'Numéro de téléphone de la société',
            'email_societe' => 'contact@societe.com',
            'total' => $transfert->total,
            'montant_du' => $transfert->total
        ]);
    }

    /**
     * Envoyer un email de confirmation du transfert.
     */
    private function envoyerEmailConfirmation(Transfert $transfert)
    {
        if (!empty($transfert->expediteur_email)) {
            Mail::to($transfert->expediteur_email)->send(new TransfertNotification($transfert));
        }
    }
}
