<?php

namespace App\Http\Controllers\Payment\Stripe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;
use App\Models\PaymentEnLigne;
use App\Traits\JsonResponseTrait;

class PaymentIntentStoreController extends Controller
{
    use JsonResponseTrait;

      public function index(Request $r)
    {
       return response()->json(['iba' => true]);  
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount'    => 'required|integer|min:50',            // centimes
            'currency'  => 'nullable|string|in:eur,usd',
            'metadata'  => 'sometimes|array',
            'order_id'  => 'sometimes|nullable|string|max:100',  // clé métier/id commande
            'force_new' => 'sometimes|boolean',                  // force l’abandon de l’ancien PI
            'dev'       => 'sometimes|boolean',                  // idempotence souple en DEV
        ]);

        $currency = $validated['currency'] ?? 'eur';
        $orderId  = $validated['order_id'] ?? null;
        $forceNew = (bool) ($validated['force_new'] ?? false);
        $isDev    = (bool) ($validated['dev'] ?? false);

        // Métadonnées enrichies (utile pour le webhook)
        $metadata = array_merge([
            'source'   => 'payment_intent',
            'order_id' => $orderId,
            'user_id'  => optional($request->user())->id,
        ], $validated['metadata'] ?? []);

        // Clé API Stripe
        $secret = config('services.stripe.secret');
        if (empty($secret)) {
            Log::error('Stripe secret manquant (services.stripe.secret)');
            return $this->responseJson(false, 'Configuration Stripe manquante', null, 500);
        }
        Stripe::setApiKey($secret);

        try {
            /**
             * 1) Si un order_id est fourni, on tente de RÉUTILISER un PaymentIntent existant
             *    (même montant/devise + statut réutilisable). Sinon, on annulera l’ancien.
             */
            if ($orderId) {
                $existing = PaymentEnLigne::query()
                    ->where('provider', 'stripe')
                    ->whereNotNull('payment_intent_id')
                    ->where('metadata->order_id', $orderId)   // JSON path (MySQL 5.7+/MariaDB 10.2+)
                    ->latest('id')
                    ->first();

                if ($existing) {
                    try {
                        $pi = PaymentIntent::retrieve($existing->payment_intent_id);

                        // Déjà payé ?
                        if ($pi->status === 'succeeded') {
                            return $this->responseJson(true, 'Paiement déjà confirmé', [
                                'clientSecret' => $pi->client_secret,
                                'id'           => $pi->id,
                                'status'       => $pi->status,
                                'alreadyPaid'  => true,
                            ]);
                        }

                        $sameAmount   = ((int) $pi->amount)   === (int) $validated['amount'];
                        $sameCurrency = (string) $pi->currency === (string) $currency;

                        $reusable = in_array($pi->status, [
                            'requires_payment_method',
                            'requires_action',
                            'requires_confirmation',
                            'processing',
                            'requires_capture',
                        ], true);

                        if (!$forceNew && $sameAmount && $sameCurrency && $reusable) {
                            // ✅ Réutilisation de l’intent existant
                            return $this->responseJson(true, 'PaymentIntent réutilisé', [
                                'clientSecret' => $pi->client_secret,
                                'id'           => $pi->id,
                                'status'       => $pi->status,
                            ]);
                        }

                        // Sinon : on annule l’ancien (si annulable) puis on créera un nouveau
                        try { PaymentIntent::cancel($pi->id); }
                        catch (\Throwable $e) {
                            Log::warning('Annulation PI échouée', ['pi' => $pi->id, 'err' => $e->getMessage()]);
                        }

                    } catch (\Throwable $e) {
                        // Si la récupération de l’intent échoue, on poursuivra vers la création
                        Log::warning('Retrieve PaymentIntent échoué', ['err' => $e->getMessage()]);
                    }
                }
            }

            /**
             * 2) Création d’un NOUVEAU PaymentIntent
             *    - Idempotence: stable en prod (basée sur order_id), aléatoire en dev
             */
            $idempotencyKey = $isDev
                ? 'pi_'.Str::uuid()
                : ($orderId ? 'pi_'.$orderId : 'pi_'.Str::uuid());

            $intent = PaymentIntent::create([
                'amount'               => (int) $validated['amount'],
                'currency'             => $currency,
                'payment_method_types' => ['card'], // CB
                'metadata'             => $metadata,
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);

            /**
             * 3) Trace locale (upsert) — statut mappé
             */
            PaymentEnLigne::updateOrCreate(
                ['payment_intent_id' => $intent->id],
                [
                    'provider'            => 'stripe',
                    'provider_payment_id' => $intent->id,
                    'session_id'          => null,
                    'status'              => PaymentEnLigne::mapStripeStatus($intent->status), // -> pending au départ
                    'amount'              => (int) $validated['amount'],
                    'currency'            => $currency,
                    'user_id'             => optional($request->user())->id,
                    'metadata'            => array_merge($metadata, ['payment_intent_id' => $intent->id]),
                    'processed_at'        => null,
                ]
            );

            return $this->responseJson(true, 'PaymentIntent créé avec succès', [
                'clientSecret' => $intent->client_secret,
                'id'           => $intent->id,
                'status'       => $intent->status,
                'livemode'     => (bool) $intent->livemode,
                'server_mode'  => str_starts_with($secret, 'sk_live_') ? 'live' : 'test',
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Stripe API error', ['msg' => $e->getMessage()]);
            return $this->responseJson(false, 'Erreur Stripe : '.$e->getMessage(), null, 422);

        } catch (\Throwable $e) {
            Log::error('PaymentIntent store failed', ['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->responseJson(false, 'Erreur serveur : '.$e->getMessage(), null, 500);
        }
    }
}
