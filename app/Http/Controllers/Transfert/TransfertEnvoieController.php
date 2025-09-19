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
     * Créer un transfert EUR -> GNF (GNF stocké en ENTIER).
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
            // 1) Taux (ENTIER)
            $tauxEchange = TauxEchange::findOrFail($request->taux_echange_id);
            $taux = (int) $tauxEchange->taux; // ex: 10700

            // 2) Montants
            $montantEuro = (float) $request->montant_euro;

            // 3) Conversion GNF (entiers)
            $montantGnf = (int) round($montantEuro * $taux, 0, PHP_ROUND_HALF_UP);

            // 4) Frais: calcul en EUR -> conversion en GNF (entier)
            $fraisEuro = $this->calculerFraisEuro($montantEuro);
            $fraisGnf  = (int) round($fraisEuro * $taux, 0, PHP_ROUND_HALF_UP);

            // 5) Total GNF (entier)
            $totalGnf = $montantGnf + $fraisGnf;

            // 6) Persistance
            $transfert = Transfert::create([
                'user_id'          => $userId,
                'beneficiaire_id'  => (int) $request->beneficiaire_id,
                'devise_source_id' => 1, // EUR
                'devise_cible_id'  => 2, // GNF
                'taux_echange_id'  => $tauxEchange->id,
                'taux_applique'    => $taux,        // ENTIER
                'montant_euro'     => $montantEuro, // DECIMAL(15,2)
                'montant_gnf'      => $montantGnf,  // ENTIER
                'frais'            => $fraisGnf,    // ENTIER
                'total'            => $totalGnf,    // ENTIER
                'statut'           => 'envoyé',
                'code'             => Transfert::generateUniqueCode(),
            ]);

            // 7) Facture liée (montants en GNF)
            $this->createFacture($transfert);

            // 8) Email (optionnel)
            $this->envoyerEmailConfirmation($transfert);

            return $this->responseJson(true, 'Transfert effectué avec succès.', $transfert->fresh(), 201);

        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Échec de la validation des données.', $e->errors(), 422);

        } catch (Exception $e) {
            \Log::error('Transfert KO', ['err' => $e->getMessage()]);
            return $this->responseJson(false, 'Erreur lors de la création du transfert.', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Règles de validation (EUR saisi, GNF dérivé).
     */
    private function validateRequest(Request $request)
    {
        return Validator::make($request->all(), [
            'beneficiaire_id' => ['required', 'exists:beneficiaires,id'],
            'taux_echange_id' => ['required', 'exists:taux_echanges,id'],
            'montant_euro'    => ['required', 'numeric', 'min:1', 'max:1000'],
        ]);
    }

    /**
     * Calcule les frais en EUR selon la table "frais".
     */
    private function calculerFraisEuro(float $montantEuro): float
    {
        $frais = Frais::where('montant_min', '<=', $montantEuro)
            ->where(function ($q) use ($montantEuro) {
                $q->where('montant_max', '>=', $montantEuro)
                  ->orWhereNull('montant_max');
            })
            ->orderBy('montant_min', 'asc')
            ->first();

        if (!$frais) return 0.0;

        return $frais->type === 'pourcentage'
            ? max(1.0, $montantEuro * ((float) $frais->valeur / 100.0))
            : (float) $frais->valeur;
    }

    /**
     * Créer la facture liée (montants en GNF).
     */
    private function createFacture(Transfert $transfert): void
    {
        Facture::create([
            'transfert_id'    => $transfert->id,
            'type'            => 'transfert',
            'statut'          => 'brouillon',
            'envoye'          => false,
            'nom_societe'     => 'FELLO',
            'adresse_societe' => '5 allé du Foehn Ostwald 67540, Strasbourg.',
            'phone_societe'   => 'Numéro de téléphone de la société',
            'email_societe'   => 'contact@societe.com',
            'total'           => $transfert->total,   // GNF entier
            'montant_du'      => $transfert->total,   // GNF entier
        ]);
    }

    /**
     * Envoi email (optionnel) à l’utilisateur connecté s’il a un email.
     */
    private function envoyerEmailConfirmation(Transfert $transfert): void
    {
        $email = $transfert->expediteur?->email;
        if ($email) {
            try {
                Mail::to($email)->send(new TransfertNotification($transfert));
            } catch (\Throwable $e) {
                \Log::warning('Email transfert non envoyé: '.$e->getMessage());
            }
        }
    }
}
