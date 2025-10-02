<?php

namespace App\Http\Controllers\Payment\Stripe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\JsonResponseTrait;

use App\Models\PaymentEnLigne;
use App\Models\Transfert;
use App\Models\TauxEchange;
use App\Models\Frais;
use App\Models\Facture;
use App\Mail\TransfertNotification;

class WebhookController extends Controller
{
    use JsonResponseTrait;

    public function handle(Request $request)
    {
        $sig     = $request->header('Stripe-Signature');
        $secret  = config('services.stripe.webhook_secret');
        $payload = $request->getContent();

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Throwable $e) {
            Log::warning('Stripe signature invalid: '.$e->getMessage());
            return $this->responseJson(false, 'Invalid signature', null, 400);
        }

        try {
            Log::info('Stripe event received', ['type' => $event->type, 'id' => $event->id]);

            // ──────────────────────────────
            // 1) CHECKOUT (page hébergée)
            // ──────────────────────────────
            if ($event->type === 'checkout.session.completed') {
                /** @var \Stripe\Checkout\Session $cs */
                $cs = $event->data->object;

                $pel = PaymentEnLigne::where('session_id', $cs->id)
                    ->orWhere('provider_payment_id', $cs->id)
                    ->first();

                if ($pel) {
                    if (!empty($cs->payment_intent) && empty($pel->payment_intent_id)) {
                        $pel->payment_intent_id = (string) $cs->payment_intent;
                    }

                    $pel->status = $cs->payment_status === 'paid'
                        ? 'succeeded'
                        : (string) $cs->payment_status;

                    // customer_details peut être null → null-safe
                    $emailFromCheckout = $cs->customer_details?->email
                        ?? $cs->customer_email
                        ?? null;

                    $oldMeta    = is_array($pel->metadata) ? $pel->metadata : [];
                    $metaStripe = $this->toArraySafe($cs->metadata ?? []);
                    $pel->metadata = array_merge($oldMeta, $metaStripe, [
                        'customer_email' => $emailFromCheckout ?? ($oldMeta['customer_email'] ?? null),
                        'last_event'     => $event->type,
                        'livemode'       => (bool) ($cs->livemode ?? false),
                        'source'         => $oldMeta['source'] ?? 'checkout',
                    ]);

                    if ($cs->payment_status === 'paid' && empty($pel->processed_at)) {
                        try {
                            $this->finalizeAfterSuccess($pel);
                            $pel->processed_at = now();
                        } catch (\Throwable $e) {
                            Log::error('Finalize (checkout) failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
                            // Stripe doit recevoir 2xx
                        }
                    }

                    $pel->save();
                } else {
                    Log::warning('Checkout session completed but no local row found', ['session_id' => $cs->id]);
                }

                return new Response('OK', 200);
            }

            // ──────────────────────────────
            // 2) PAYMENT INTENT (Elements & co)
            //  - Whitelist d’événements utiles
            //  - Jamais rétrograder un statut "succeeded"
            // ──────────────────────────────
            if (str_starts_with($event->type, 'payment_intent.')) {
                /** @var \Stripe\PaymentIntent $pi */
                $pi = $event->data->object;

                // Seuls ces events doivent impacter le statut
                $typeToStatus = [
                    'payment_intent.succeeded'      => 'succeeded',
                    'payment_intent.canceled'       => 'canceled',
                    'payment_intent.payment_failed' => 'failed',
                    'payment_intent.processing'     => 'processing',
                    // IGNORE: created / requires_* / updated...
                ];

                if (!isset($typeToStatus[$event->type])) {
                    Log::info('PI event ignored to prevent demotion', ['type' => $event->type]);
                    return new Response('OK', 200);
                }

                $newStatus = $typeToStatus[$event->type];

                $pel = PaymentEnLigne::firstOrCreate(
                    ['payment_intent_id' => (string) $pi->id],
                    [
                        'provider'            => 'stripe',
                        'provider_payment_id' => (string) $pi->id,
                        'status'              => 'pending',
                    ]
                );

                // ⚠️ Ne jamais rétrograder un succeeded
                if ($pel->status === 'succeeded' && $newStatus !== 'succeeded') {
                    Log::info('Skip status demotion', ['current' => $pel->status, 'new' => $newStatus]);
                    return new Response('OK', 200);
                }

                $pel->amount   = (int) ($pi->amount ?? $pel->amount ?? 0);
                $pel->currency = (string) ($pi->currency ?? $pel->currency ?? 'eur');
                $pel->status   = $newStatus;

                $oldMeta    = is_array($pel->metadata) ? $pel->metadata : [];
                $metaStripe = $this->toArraySafe($pi->metadata ?? []);
                $pel->metadata = array_merge($oldMeta, $metaStripe, [
                    'customer_email' => $oldMeta['customer_email'] ?? null,
                    'last_event'     => $event->type,
                    'livemode'       => (bool) ($pi->livemode ?? false),
                ]);

                if ($newStatus === 'succeeded' && empty($pel->processed_at)) {
                    try {
                        $this->finalizeAfterSuccess($pel);
                        $pel->processed_at = now();
                    } catch (\Throwable $e) {
                        Log::error('Finalize (PI) failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
                    }
                }

                $pel->save();

                return new Response('OK', 200);
            }

            Log::info('Stripe event ignored', ['type' => $event->type]);
            return new Response('OK', 200);

        } catch (\Throwable $e) {
            Log::error('Webhook handler error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return new Response('OK', 200); // stop retries Stripe
        }
    }

    /**
     * Finalisation métier (idempotente) :
     * - crée Transfert à partir des metadata (beneficiaire_id, taux_echange_id, montant_envoie…)
     * - crée Facture
     * - envoie email
     * - marque metadata.transfert_id (idempotence)
     */
    protected function finalizeAfterSuccess(PaymentEnLigne $pel): void
    {
        $meta = is_array($pel->metadata) ? $pel->metadata : [];
        if (!empty($pel->processed_at) || !empty($meta['transfert_id'])) {
            return; // déjà traité
        }

        DB::transaction(function () use ($pel) {
            /** @var PaymentEnLigne $locked */
            $locked = PaymentEnLigne::whereKey($pel->id)->lockForUpdate()->first();
            $lockedMeta = is_array($locked->metadata) ? $locked->metadata : [];

            if (!empty($locked->processed_at) || !empty($lockedMeta['transfert_id'])) {
                return;
            }

            $beneficiaireId = isset($lockedMeta['beneficiaire_id']) ? (int) $lockedMeta['beneficiaire_id'] : null;
            $tauxId         = isset($lockedMeta['taux_echange_id']) ? (int) $lockedMeta['taux_echange_id'] : null;
            $montantEuro    = isset($lockedMeta['montant_envoie']) ? (float) $lockedMeta['montant_envoie'] : null;
            $modeReception  = $lockedMeta['mode_reception'] ?? Transfert::MODE_RETRAIT_CASH;

            $userId         = $locked->user_id ?: (isset($lockedMeta['user_id']) ? (int) $lockedMeta['user_id'] : null);
            $fraisEuroMeta  = isset($lockedMeta['frais_eur']) ? (float) $lockedMeta['frais_eur'] : null;
            $totalTtcMeta   = isset($lockedMeta['total_ttc']) ? (float) $lockedMeta['total_ttc'] : null;
            $customerEmail  = $lockedMeta['customer_email'] ?? null;

            if (!$beneficiaireId || !$tauxId || $montantEuro === null) {
                Log::warning('Finalize skipped: missing required metadata', [
                    'payment_en_ligne_id' => $locked->id,
                    'meta' => $lockedMeta,
                ]);
                return;
            }

            $taux = (int) optional(TauxEchange::find($tauxId))->taux;
            if ($taux <= 0) {
                Log::warning('Finalize skipped: invalid taux', ['taux_id' => $tauxId, 'taux' => $taux]);
                return;
            }

            $fraisEuro = $fraisEuroMeta ?? $this->calculerFraisEuro($montantEuro);
            $totalEuro = $totalTtcMeta ?? round($montantEuro + $fraisEuro, 2, PHP_ROUND_HALF_UP);

            $montantGnf = (int) round($montantEuro * $taux, 0, PHP_ROUND_HALF_UP);
            $totalGnf   = $montantGnf;

            $transfert = Transfert::create([
                'user_id'          => $userId,
                'beneficiaire_id'  => $beneficiaireId,
                'devise_source_id' => 1,
                'devise_cible_id'  => 2,
                'taux_echange_id'  => $tauxId,
                'taux_applique'    => $taux,
                'montant_envoie'   => $montantEuro,
                'frais'            => $fraisEuro,
                'total_ttc'        => $totalEuro,
                'montant_gnf'      => $montantGnf,
                'total_gnf'        => $totalGnf,
                'statut'           => Transfert::STATUT_ENVOYE,
                'mode_reception'   => $modeReception,
            ]);

            $facture = Facture::create([
                'transfert_id'    => $transfert->id,
                'type'            => 'transfert',
                'statut'          => 'brouillon',
                'envoye'          => false,
                'nom_societe'     => 'FELLO',
                'adresse_societe' => '5 allé du Foehn Ostwald 67540, Strasbourg.',
                'phone_societe'   => 'Numéro de téléphone de la société',
                'email_societe'   => 'contact@societe.com',
                'total'           => $transfert->total_ttc,
                'montant_du'      => $transfert->total_ttc,
            ]);

            try {
                $to = $transfert->expediteur->email ?? $customerEmail;
                if ($to) {
                    Mail::to($to)->send(new TransfertNotification($transfert));
                }
            } catch (\Throwable $e) {
                Log::warning('Email transfert non envoyé: '.$e->getMessage());
            }

            $lockedMeta['transfert_id'] = $transfert->id;
            if (isset($facture->id)) {
                $lockedMeta['facture_id'] = $facture->id;
            }
            $locked->metadata = $lockedMeta;
            $locked->save();
        });
    }

    // ────────────────────────────── Helpers ──────────────────────────────

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

    private function toArraySafe($meta): array
    {
        if (is_array($meta)) return $meta;
        if (is_object($meta) && method_exists($meta, 'toArray')) {
            return $meta->toArray();
        }
        return [];
    }
}
