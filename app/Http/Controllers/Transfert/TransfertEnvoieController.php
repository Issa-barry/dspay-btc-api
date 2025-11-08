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
            $montantEuro = (float) $request->montant_envoie;

            // 3) Frais en € (jamais convertis)
            $fraisEuro  = $this->calculerFraisEuro($montantEuro);
            $totalEuro  = round($montantEuro + $fraisEuro, 2, PHP_ROUND_HALF_UP);

            // 4) Conversion du principal en GNF (les frais ne sont pas convertis)
            $montantGnf = (int) round($montantEuro * $taux, 0, PHP_ROUND_HALF_UP);
            $totalGnf   = $montantGnf; // pas de frais en GNF

            // 5) Persistance
            $transfert = Transfert::create([
                'user_id'             => $userId,
                'beneficiaire_id'     => (int) $request->beneficiaire_id,
                'devise_source_id'    => 1, // EUR
                'devise_cible_id'     => 2, // GNF
                'taux_echange_id'     => $tauxEchange->id,
                'taux_applique'       => $taux,
                'montant_envoie'      => $montantEuro,
                'frais'               => $fraisEuro,
                'total_ttc'           => $totalEuro,
                'amount'              => $montantGnf,
                'total_gnf'           => $totalGnf,
                'statut'              => Transfert::STATUT_ENVOYE,
                'serviceId'           => $request->input('serviceId', Transfert::SERVICE_ORANGE_MONEY),
                'recipientTel'        => $request->input('recipientTel'),        // ← Nouveau
                'accountId'           => $request->input('accountId'),           // ← Nouveau
                'customerPhoneNumber' => $request->input('customerPhoneNumber'), // ← Nouveau
                'code'                => Transfert::generateUniqueCode(),
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
        $serviceId = $request->input('serviceId', Transfert::SERVICE_ORANGE_MONEY);
        
        // Règles de base
        $rules = [
            'beneficiaire_id' => ['required', 'exists:beneficiaires,id'],
            'taux_echange_id' => ['required', 'exists:taux_echanges,id'],
            'montant_envoie'  => ['required', 'numeric', 'min:1', 'max:10000'],
            'serviceId'       => ['nullable', 'in:'.implode(',', Transfert::SERVICES)],
        ];

        // Validation conditionnelle selon le type de service
        if (in_array($serviceId, Transfert::SERVICES_TEL)) {
            // Service utilisant le téléphone (Orange Money, MTN, etc.)
            $rules['recipientTel'] = ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'];
            $rules['accountId'] = ['nullable']; // Non utilisé mais autorisé
            $rules['customerPhoneNumber'] = ['nullable']; // Non requis pour ce type
            
        } elseif (in_array($serviceId, Transfert::SERVICES_ACCOUNT)) {
            // Service utilisant un numéro de compte (KS Pay, Paycard, etc.)
            $rules['accountId'] = ['required', 'string', 'max:50'];
            $rules['customerPhoneNumber'] = ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'];
            $rules['recipientTel'] = ['nullable']; // Non utilisé mais autorisé
            
        } else {
            // Par défaut, au moins l'un des deux doit être fourni
            $rules['recipientTel'] = ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'];
            $rules['accountId'] = ['nullable', 'string', 'max:50'];
            $rules['customerPhoneNumber'] = ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'];
        }

        $validator = Validator::make($request->all(), $rules);

        // Validation supplémentaire : au moins recipientTel OU accountId doit être fourni
        $validator->after(function ($validator) use ($request) {
            if (empty($request->recipientTel) && empty($request->accountId)) {
                $validator->errors()->add(
                    'recipientTel', 
                    'Vous devez fournir soit un numéro de téléphone (recipientTel) soit un numéro de compte (accountId).'
                );
            }
        });

        return $validator;
    }

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
            $pourcent = (float) $frais->valeur;
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
            'total'           => $t->total_ttc,
            'montant_du'      => $t->total_ttc,
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