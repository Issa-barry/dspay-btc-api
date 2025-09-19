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

    public function store(Request $request)
    {
        $userId = $request->user()?->id ?? Auth::id();
        if (!$userId) {
            return $this->responseJson(false, 'Non authentifié.', null, 401);
        }

        $validator = $this->validateRequest($request);
        if ($validator->fails()) {
            return $this->responseJson(false, 'Validation échouée.', $validator->errors(), 422);
        }

        try {
            // 1) Taux ENTIER (ex: 10700)
            $tauxEchange = TauxEchange::findOrFail($request->taux_echange_id);
            $taux = (int) $tauxEchange->taux;

            // 2) Montant saisi en €
            $montantEuro = (float) $request->montant_euro;

            // 3) Frais en € (jamais convertis)
            $fraisEuro  = $this->calculerFraisEuro($montantEuro);
            $totalEuro  = round($montantEuro + $fraisEuro, 2, PHP_ROUND_HALF_UP);

            // 4) Conversion du principal en GNF (les frais ne sont pas convertis)
            $montantGnf = (int) round($montantEuro * $taux, 0, PHP_ROUND_HALF_UP);
            $totalGnf   = $montantGnf; // pas de frais en GNF

            // 5) Persistance
            $transfert = Transfert::create([
                'user_id'          => $userId,
                'beneficiaire_id'  => (int) $request->beneficiaire_id,
                'devise_source_id' => 1, // EUR
                'devise_cible_id'  => 2, // GNF
                'taux_echange_id'  => $tauxEchange->id,
                'taux_applique'    => $taux,        // ENTIER
                'montant_euro'     => $montantEuro, // DECIMAL
                'frais_eur'        => $fraisEuro,   // DECIMAL
                'total_eur'        => $totalEuro,   // DECIMAL
                'montant_gnf'      => $montantGnf,  // ENTIER
                'total_gnf'        => $totalGnf,    // ENTIER
                'statut'           => 'envoyé',
                'code'             => Transfert::generateUniqueCode(),
            ]);

            // 6) Facture (en €)
            $this->createFacture($transfert);

            // 7) Email (optionnel)
            $this->envoyerEmailConfirmation($transfert);

            return $this->responseJson(true, 'Transfert effectué avec succès.', $transfert->fresh(), 201);

        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Échec de la validation des données.', $e->errors(), 422);
        } catch (Exception $e) {
            \Log::error('Transfert KO', ['err' => $e->getMessage()]);
            return $this->responseJson(false, 'Erreur lors de la création du transfert.', ['message' => $e->getMessage()], 500);
        }
    }

    private function validateRequest(Request $request)
    {
        return Validator::make($request->all(), [
            'beneficiaire_id' => ['required', 'exists:beneficiaires,id'],
            'taux_echange_id' => ['required', 'exists:taux_echanges,id'],
            'montant_euro'    => ['required', 'numeric', 'min:1', 'max:1000'],
        ]);
    }

    // Frais en € — type 'pourcentage' => valeur en POURCENT (5 pour 5%), type 'fixe' => valeur en €
    private function calculerFraisEuro(float $montantEuro): float
    {
        $frais = Frais::where('montant_min', '<=', $montantEuro)
            ->where(function ($q) use ($montantEuro) {
                $q->where('montant_max', '>=', $montantEuro)->orWhereNull('montant_max');
            })
            ->orderBy('montant_min', 'asc')
            ->first();

        if (!$frais) return 0.0;

        if ($frais->type === 'pourcentage') {
            $pourcent = (float) $frais->valeur; // ex: 5 => 5%
            return round($montantEuro * ($pourcent / 100.0), 2, PHP_ROUND_HALF_UP);
        }

        return round((float) $frais->valeur, 2, PHP_ROUND_HALF_UP);
    }

    private function createFacture(Transfert $t): void
    {
        Facture::create([
            'transfert_id'    => $t->id,
            'type'            => 'transfert',
            'statut'          => 'brouillon',
            'envoye'          => false,
            'nom_societe'     => 'FELLO',
            'adresse_societe' => '5 allé du Foehn Ostwald 67540, Strasbourg.',
            'phone_societe'   => 'Numéro de téléphone de la société',
            'email_societe'   => 'contact@societe.com',
            'total'           => $t->total_eur,  // facture en €
            'montant_du'      => $t->total_eur,  // facture en €
        ]);
    }

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
