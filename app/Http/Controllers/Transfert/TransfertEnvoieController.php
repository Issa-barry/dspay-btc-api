<?php

namespace App\Http\Controllers\Transfert;

use App\Http\Controllers\Controller;
use App\Mail\TransfertNotification;
use App\Models\Facture;
use App\Models\Frais;
use App\Models\TauxEchange;
use App\Models\Transfert;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Exception;

class TransfertEnvoieController extends Controller
{
    use JsonResponseTrait;

    /**
     * Créer un transfert EUR -> GNF avec un taux d'échange existant.
     */
    public function store(Request $request)
    {
        // Auth obligatoire
        $userId = $request->user()?->id ?? Auth::id();
        if (!$userId) {
            return $this->responseJson(false, 'Non authentifié.', null, 401);
        }

        // Validation
        $validator = $this->validateRequest($request);
        if ($validator->fails()) {
            return $this->responseJson(false, 'Validation échouée.', $validator->errors(), 422);
        }

        try {
            // Taux
            $tauxEchange = TauxEchange::findOrFail($request->taux_echange_id);
            $taux = (float)$tauxEchange->taux;

            // Montants
            $montantEuro = (float)$request->montant_euro;
            $montantGnf  = round($montantEuro * $taux, 2);

            // Frais / total (sur le montant EUR)
            $frais = $this->calculerFrais($montantEuro);
            $total = $montantEuro + $frais;

            // Données à persister
            $transfertData = [
                'user_id'          => $userId,                       // expéditeur = user connecté
                'beneficiaire_id'  => (int)$request->beneficiaire_id, // receveur = bénéficiaire choisi

                'devise_source_id' => 1, // EUR
                'devise_cible_id'  => 2, // GNF

                'taux_echange_id'  => $tauxEchange->id,
                'taux_applique'    => $taux,

                'montant_euro'     => $montantEuro,
                'montant_gnf'      => $montantGnf,

                'frais'            => (int)$frais,
                'total'            => $total,

                'statut'           => 'en_cours',
                'code'             => Transfert::generateUniqueCode(),
            ];

            $transfert = Transfert::create($transfertData);

            // Facture associée
            $this->createFacture($transfert);

            // (Optionnel) mail à l’expéditeur si l’utilisateur a un email
            $this->envoyerEmailConfirmation($transfert);

            return $this->responseJson(true, 'Transfert effectué avec succès.', $transfert->fresh(), 201);

        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Échec de la validation des données.', $e->errors(), 422);

        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la création du transfert.', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Règles de validation (nouvelle structure).
     */
    private function validateRequest(Request $request)
    {
        return Validator::make($request->all(), [
            'beneficiaire_id' => ['required', 'exists:beneficiaires,id'],
            'taux_echange_id' => ['required', 'exists:taux_echanges,id'],
            'montant_euro'    => ['required', 'numeric', 'min:1', 'max:1000'], // limite 1000 €
        ]);
    }

    /**
     * Calcul de frais selon bornes (table frais).
     */
    private function calculerFrais(float $montantEuro): int|float
    {
        $frais = Frais::where('montant_min', '<=', $montantEuro)
            ->where(function ($q) use ($montantEuro) {
                $q->where('montant_max', '>=', $montantEuro)
                  ->orWhereNull('montant_max');
            })
            ->orderBy('montant_min', 'asc')
            ->first();

        if (!$frais) return 0;

        return $frais->type === 'pourcentage'
            ? max(1, $montantEuro * ($frais->valeur / 100))
            : (float)$frais->valeur;
    }

    /**
     * Créer la facture liée.
     */
    private function createFacture(Transfert $transfert): void
    {
        Facture::create([
            'transfert_id'   => $transfert->id,
            'type'           => 'transfert',
            'statut'         => 'brouillon',
            'envoye'         => false,
            'nom_societe'    => 'FELLO',
            'adresse_societe'=> '5 allé du Foehn Ostwald 67540, Strasbourg.',
            'phone_societe'  => 'Numéro de téléphone de la société',
            'email_societe'  => 'contact@societe.com',
            'total'          => $transfert->total,
            'montant_du'     => $transfert->total,
        ]);
    }

    /**
     * Envoi email (optionnel) à l’utilisateur connecté s’il a un email.
     */
    private function envoyerEmailConfirmation(Transfert $transfert): void
    {
        $email = $transfert->expediteur?->email; // relation user via user_id
        if ($email) {
            Mail::to($email)->send(new TransfertNotification($transfert));
        }
    }
}
