<?php

namespace App\Http\Controllers\Payment\Stripe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Stripe\Stripe;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Exception\ApiErrorException;
use App\Models\PaymentEnLigne;
use App\Models\Transfert;
use App\Traits\JsonResponseTrait;

class CheckoutSessionStoreController extends Controller
{
    use JsonResponseTrait;

    /**
     * Création d'une session Stripe Checkout
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount'         => 'required|integer|min:50', // en centimes
            'currency'       => 'nullable|string|in:eur,usd',
            'success_url'    => 'required|url',
            'cancel_url'     => 'required|url',
            'customer_email' => 'nullable|email',
            'metadata'       => 'nullable|array',
            'order_id'       => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->responseJson(false, 'Erreur de validation', [
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $currency = $validated['currency'] ?? 'eur';
        $success  = rtrim($validated['success_url'], '/');
        $cancel   = rtrim($validated['cancel_url'], '/');
        $orderId  = $validated['order_id'] ?? (string) Str::uuid();

        // Jeton d'annulation unique
        $cancelToken = Str::uuid();

        $metadata = array_merge([
            'source'       => 'checkout',
            'order_id'     => $orderId,
            'cancel_token' => $cancelToken,
        ], $validated['metadata'] ?? []);

        // Construction des URLs avec paramètres
        $successUrl = $success . '?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl  = $cancel . '?order_id=' . urlencode($orderId) . '&cancel_token=' . urlencode($cancelToken);

        $idempotencyKey = 'checkout_' . $orderId;

        $secret = config('services.stripe.secret');
        if (empty($secret)) {
            Log::error('Stripe secret manquant (services.stripe.secret)');
            return $this->responseJson(false, 'Configuration Stripe manquante', null, 500);
        }

        Stripe::setApiKey($secret);

        try {
            $session = CheckoutSession::create([
                'mode'                       => 'payment',
                'success_url'                => $successUrl,
                'cancel_url'                 => $cancelUrl,
                'customer_email'             => $validated['customer_email'] ?? null,
                'allow_promotion_codes'      => true,
                'billing_address_collection' => 'auto',
                'client_reference_id'        => $orderId,
                'line_items' => [[
                    'price_data' => [
                        'currency'     => $currency,
                        'unit_amount'  => (int) $validated['amount'],
                        'product_data' => ['name' => 'Paiement DSPay'],
                    ],
                    'quantity' => 1,
                ]],
                'metadata' => $metadata,
                'payment_intent_data' => [
                    'metadata' => $metadata,
                ],
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);

            PaymentEnLigne::updateOrCreate(
                ['session_id' => $session->id],
                [
                    'provider'            => 'stripe',
                    'provider_payment_id' => $session->id,
                    'payment_intent_id'   => null,
                    'status'              => 'pending',
                    'amount'              => (int) $validated['amount'],
                    'currency'            => $currency,
                    'user_id'             => optional($request->user())->id,
                    'metadata'            => [
                        'session_id'   => $session->id,
                        'order_id'     => $orderId,
                        'cancel_token' => $cancelToken,
                    ] + $metadata,
                    'processed_at'        => null,
                ]
            );

            return $this->responseJson(true, 'Checkout session créée', [
                'id'  => $session->id,
                'url' => $session->url,
            ], 201);

        } catch (ApiErrorException $e) {
            Log::warning('Erreur Stripe Checkout', ['msg' => $e->getMessage()]);
            return $this->responseJson(false, 'Erreur Stripe : ' . $e->getMessage(), null, 422);

        } catch (\Throwable $e) {
            Log::error('CheckoutSession store failed', [
                'msg'   => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->responseJson(false, 'Erreur serveur : ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Lorsqu'un utilisateur clique sur le bouton "Retour" (cancel_url)
     */
    public function cancel(Request $request)
    {
        $orderId = $request->query('order_id');
        $token   = $request->query('cancel_token');

        if (!$orderId) {
            return $this->responseJson(false, 'Paramètre order_id manquant', null, 422);
        }

        $pel = PaymentEnLigne::where('metadata->order_id', $orderId)->first();

        if (!$pel) {
            return $this->responseJson(false, 'Session inconnue', null, 404);
        }

        // Vérifie le token d’annulation
        if ($token && data_get($pel->metadata, 'cancel_token') !== $token) {
            return $this->responseJson(false, 'Jeton invalide', null, 403);
        }

        if (in_array($pel->status, ['succeeded', 'paid', 'processing'])) {
            return $this->responseJson(true, 'Paiement déjà traité', [
                'session_id' => $pel->session_id,
                'status'     => $pel->status
            ]);
        }

        $pel->update([
            'status'       => 'canceled',
            'processed_at' => now(),
        ]);

        Log::info('Paiement annulé par utilisateur', ['order_id' => $orderId]);

        return $this->responseJson(true, 'Paiement annulé par l’utilisateur', [
            'session_id' => $pel->session_id,
            'status'     => $pel->status
        ]);
    }

    /**
     * Récupération de l’état d’une session Stripe
     */
    public function show(string $sessionId)
    {
        $pel = PaymentEnLigne::where('session_id', $sessionId)->first();

        if (!$pel) {
            return $this->responseJson(false, 'Session inconnue', null, 404);
        }

        $meta = is_array($pel->metadata) ? $pel->metadata : [];
        $transfert = null;

        if (!empty($meta['transfert_id'])) {
            $transfert = Transfert::with('beneficiaire')->find($meta['transfert_id']);
        }

        return $this->responseJson(true, 'État de la session Stripe', [
            'session_id'   => $pel->session_id,
            'status'       => $pel->status,
            'amount'       => $pel->amount,
            'currency'     => $pel->currency,
            'processed_at' => $pel->processed_at,
            'metadata'     => $meta,
            'transfert_id' => $meta['transfert_id'] ?? null,
            'transfert'    => $transfert,
        ]);
    }
}
