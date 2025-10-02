<?php

namespace App\Http\Controllers\Payment\Stripe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\PaymentIntent;

use App\Models\PaymentEnLigne;
use App\Models\Transfert;
use App\Models\TauxEchange;
use App\Models\Frais;
use App\Models\Facture;
use App\Mail\TransfertNotification;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $sig = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        Log::info('🎯 Webhook Stripe reçu', [
            'has_signature' => !empty($sig),
            'secret_configured' => !empty($secret),
            'event_type' => $request->input('type'),
        ]);

        try {
            $event = Webhook::constructEvent($request->getContent(), $sig, $secret);
            Log::info('✅ Signature webhook validée', ['event_type' => $event->type]);
        } catch (\Throwable $e) {
            Log::error('❌ Signature webhook invalide', [
                'error' => $e->getMessage(),
                'signature_present' => !empty($sig),
            ]);
            return response()->json(['received' => false], 400);
        }

        if (!str_starts_with($event->type, 'payment_intent.')) {
            Log::debug('⏭️ Événement ignoré (pas un PaymentIntent)', ['type' => $event->type]);
            return response()->json(['received' => true]);
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        /** @var \Stripe\PaymentIntent $pi */
        $pi = $event->data->object;
        
        try {
            $pi = PaymentIntent::retrieve($pi->id);
            Log::info('📦 PaymentIntent récupéré', [
                'id' => $pi->id,
                'status' => $pi->status,
                'amount' => $pi->amount,
            ]);
        } catch (\Throwable $e) {
            Log::warning('⚠️ Impossible de récupérer le PI', ['error' => $e->getMessage()]);
        }

        // Upsert (ou find) notre trace locale
        $pel = PaymentEnLigne::firstOrCreate(
            ['payment_intent_id' => $pi->id],
            [
                'provider'            => 'stripe',
                'provider_payment_id' => $pi->id,
                'amount'              => (int) $pi->amount,
                'currency'            => (string) $pi->currency,
                'status'              => 'pending',
                'metadata'            => (array) $pi->metadata,
            ]
        );

        Log::info('💾 PaymentEnLigne trouvé/créé', [
            'id' => $pel->id,
            'was_recently_created' => $pel->wasRecentlyCreated,
        ]);

        // Mapping des statuts
        $map = [
            'succeeded'  => 'succeeded',
            'processing' => 'processing',
            'canceled'   => 'canceled',
            'requires_payment_method' => 'pending',
            'requires_action'         => 'pending',
            'requires_confirmation'   => 'pending',
        ];
        
        $oldStatus = $pel->status;
        $pel->status = $map[$pi->status] ?? 'pending';
        $pel->metadata = array_merge($pel->metadata ?? [], [
            'last_event' => $event->type,
            'livemode'   => (bool) $pi->livemode,
        ]);

        Log::info('🔄 Mise à jour statut', [
            'old' => $oldStatus,
            'new' => $pel->status,
            'pi_status' => $pi->status,
        ]);

        // Si paiement OK → créer Transfert + Facture + mail (1 seule fois)
        if ($pi->status === 'succeeded') {
            Log::info('💰 Paiement succeeded → finalisation', ['pi_id' => $pi->id]);
            $this->finalizeAfterSuccess($pel);
            $pel->processed_at = Carbon::now();
        }

        $pel->save();
        Log::info('✅ PaymentEnLigne sauvegardé', ['id' => $pel->id]);

        return response()->json(['received' => true]);
    }

    /**
     * Crée Transfert + Facture + Email en s'appuyant sur les metadata du PaymentIntent.
     */
    private function finalizeAfterSuccess(PaymentEnLigne $pel): void
    {
        // Evite doublon si webhook reçu plusieurs fois
        if (!empty($pel->metadata['transfert_id'])) {
            Log::info('⏭️ Transfert déjà créé, skip', ['transfert_id' => $pel->metadata['transfert_id']]);
            return;
        }

        Log::info('🚀 Début finalisation transfert', ['pel_id' => $pel->id]);

        $m = $pel->metadata ?? [];

        // Récup métadonnées envoyées depuis le front
        $userId         = (int)($m['user_id'] ?? $pel->user_id ?? 0) ?: null;
        $beneficiaireId = (int)($m['beneficiaire_id'] ?? 0);
        $tauxId         = (int)($m['taux_echange_id'] ?? 0);
        $montantEuro    = (float)($m['montant_envoie'] ?? 0);
        $modeReception  = (string)($m['mode_reception'] ?? Transfert::MODE_RETRAIT_CASH);

        Log::info('📊 Métadonnées extraites', [
            'user_id' => $userId,
            'beneficiaire_id' => $beneficiaireId,
            'taux_id' => $tauxId,
            'montant_euro' => $montantEuro,
        ]);

        if (!$beneficiaireId || !$tauxId || !$montantEuro) {
            Log::error('❌ Métadonnées incomplètes, impossible de créer le transfert', [
                'metadata' => $m,
            ]);
            return;
        }

        try {
            // Taux ENTIER (ex: 10700)
            $tauxE = TauxEchange::findOrFail($tauxId);
            $taux  = (int) $tauxE->taux;

            // Frais (même logique que ton contrôleur)
            $fraisEuro = $this->calcFraisEuro($montantEuro);
            $totalEuro = round($montantEuro + $fraisEuro, 2, PHP_ROUND_HALF_UP);

            // Conversion en GNF (les frais ne sont pas convertis)
            $montantGnf = (int) round($montantEuro * $taux, 0, PHP_ROUND_HALF_UP);
            $totalGnf   = $montantGnf;

            // Création du Transfert (le modèle génère le code)
            $t = Transfert::create([
                'user_id'          => $userId,
                'beneficiaire_id'  => $beneficiaireId,
                'devise_source_id' => 1, // EUR
                'devise_cible_id'  => 2, // GNF
                'taux_echange_id'  => $tauxE->id,
                'taux_applique'    => $taux,
                'montant_envoie'   => $montantEuro,
                'frais'            => $fraisEuro,
                'total_ttc'        => $totalEuro,
                'montant_gnf'      => $montantGnf,
                'total_gnf'        => $totalGnf,
                'statut'           => Transfert::STATUT_ENVOYE,
                'mode_reception'   => $modeReception,
            ]);

            Log::info('✅ Transfert créé', [
                'id' => $t->id,
                'code' => $t->code,
            ]);

            // Facture (en €)
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

            Log::info('📄 Facture créée', ['transfert_id' => $t->id]);

            // E-mail au client (optionnel si pas d'email)
            $email = $t->expediteur?->email;
            if ($email) {
                try {
                    Mail::to($email)->send(new TransfertNotification($t));
                    Log::info('📧 Email envoyé', ['to' => $email]);
                } catch (\Throwable $e) {
                    Log::warning('⚠️ Email transfert non envoyé', ['error' => $e->getMessage()]);
                }
            }

            // Lier la trace paiement au transfert + garder le code
            $pel->metadata = array_merge($pel->metadata ?? [], [
                'transfert_id' => $t->id,
                'transfert_code' => $t->code ?? null,
            ]);

            Log::info('🎉 Finalisation terminée avec succès');
            
        } catch (\Throwable $e) {
            Log::error('❌ Erreur lors de la finalisation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function calcFraisEuro(float $montantEuro): float
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
}