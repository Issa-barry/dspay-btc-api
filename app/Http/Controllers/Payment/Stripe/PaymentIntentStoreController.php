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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount'     => 'required|integer|min:50',     // centimes (ex: 1000 = 10,00 €)
            'currency'   => 'nullable|string|in:eur,usd',  // CB => EUR/USD
            'metadata'   => 'array',
            'order_id'   => 'nullable|string|max:100',
            'force_new'  => 'sometimes|boolean',           // annule l’ancien PI si même order_id
            'dev'        => 'sometimes|boolean',           // en DEV, idempotence plus souple
        ]);

        $currency  = $validated['currency'] ?? 'eur';
        $orderId   = $validated['order_id'] ?? null;
        $forceNew  = (bool) ($validated['force_new'] ?? false);
        $isDev     = (bool) ($validated['dev'] ?? false);

        // Métadonnées enrichies
        $metadata = array_merge([
            'source'   => 'payment_intent',
            'order_id' => $orderId,
        ], $validated['metadata'] ?? []);

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            // ─────────────────────────────────────────────────────────────────────────────
            // 1) Si order_id fourni : tenter de RÉUTILISER un PaymentIntent existant
            // ─────────────────────────────────────────────────────────────────────────────
            if ($orderId) {
                $existing = PaymentEnLigne::where('provider', 'stripe')
                    ->where('metadata->order_id', $orderId)
                    ->whereNotNull('payment_intent_id')
                    ->first();

                if ($existing) {
                    // Si on force la création d’un nouveau PI → on annule l’ancien
                    if ($forceNew) {
                        try {
                            PaymentIntent::cancel($existing->payment_intent_id);
                        } catch (\Throwable $e) {
                            Log::warning('Annulation PI échouée (force_new)', [
                                'pi' => $existing->payment_intent_id,
                                'err' => $e->getMessage()
                            ]);
                        }
                        // on poursuivra vers la création d’un nouveau PI (plus bas)
                    } else {
                        // Sinon on tente la réutilisation si paramètres identiques
                        $pi = PaymentIntent::retrieve($existing->payment_intent_id);

                        $sameAmount   = ((int) $pi->amount)   === (int) $validated['amount'];
                        $sameCurrency = (string) $pi->currency === (string) $currency;

                        $reusableStatuses = [
                            'requires_payment_method',
                            'requires_confirmation',
                            'requires_action',
                            'processing',
                            'requires_capture'
                        ];

                        if ($sameAmount && $sameCurrency && in_array($pi->status, $reusableStatuses, true)) {
                            // ✅ Réutilisation : renvoyer le client_secret existant
                            return $this->responseJson(true, 'PaymentIntent réutilisé', [
                                'clientSecret' => $pi->client_secret,
                                'id'           => $pi->id,
                                'status'       => $pi->status,
                            ]);
                        }

                        // Montant/devise changés → on annule l’ancien et on créera un nouveau
                        try {
                            PaymentIntent::cancel($pi->id);
                        } catch (\Throwable $e) {
                            Log::warning('Annulation PI échouée (params modifiés)', [
                                'pi' => $pi->id,
                                'err' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }

            // ─────────────────────────────────────────────────────────────────────────────
            // 2) Création d’un NOUVEAU PaymentIntent (CB uniquement)
            // ─────────────────────────────────────────────────────────────────────────────
            // Idempotence :
            // - En prod : si order_id → clé stable basée sur order_id
            // - En dev  : clé aléatoire pour éviter les conflits pendant les tests
            $idempotencyKey = $isDev
                ? ('pi_'.Str::uuid())                   // DEV: clé aléatoire
                : ($orderId ? 'pi_'.$orderId : 'pi_'.Str::uuid()); // PROD: stable si order_id

            $intent = PaymentIntent::create([
                'amount'               => $validated['amount'],
                'currency'             => $currency,
                'payment_method_types' => ['card'],     // ⬅️ CB uniquement
                'metadata'             => $metadata,
                // 'description'        => 'Paiement DSPay', // optionnel
                // 'statement_descriptor'=> 'DSPAY',          // optionnel, contraintes pays
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);

            // ─────────────────────────────────────────────────────────────────────────────
            // 3) Trace/Upsert en base
            // ─────────────────────────────────────────────────────────────────────────────
            PaymentEnLigne::updateOrCreate(
                ['payment_intent_id' => $intent->id],
                [
                    'provider'            => 'stripe',
                    'provider_payment_id' => $intent->id, // compat
                    'session_id'          => null,
                    'status'              => 'pending',
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
                'livemode'     => (bool) $intent->livemode, // ← vrai en live
                'server_mode'  => str_starts_with(config('services.stripe.secret'), 'sk_live_') ? 'live' : 'test',
            ]);


        } catch (ApiErrorException $e) {
            Log::error('Stripe API error', ['msg' => $e->getMessage()]);
            return $this->responseJson(false, 'Erreur Stripe : '.$e->getMessage(), null, 422);

        } catch (\Throwable $e) {
            Log::error('PaymentIntent store failed', ['msg' => $e->getMessage()]);
            return $this->responseJson(false, 'Erreur serveur : '.$e->getMessage(), null, 500);
        }
    }
}
