<?php

namespace App\Http\Controllers\Payment\Stripe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Exception\ApiErrorException;
use App\Models\PaymentEnLigne;
use App\Traits\JsonResponseTrait;

class CheckoutSessionStoreController extends Controller
{
    use JsonResponseTrait;

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount'         => 'required|integer|min:50',          // centimes
            'currency'       => 'nullable|string|in:eur,usd',
            'success_url'    => 'required|url',
            'cancel_url'     => 'required|url',
            'customer_email' => 'sometimes|nullable|email',
            'metadata'       => 'sometimes|array',
            'order_id'       => 'sometimes|nullable|string|max:100',
        ]);

        $currency = $validated['currency'] ?? 'eur';
        $orderId  = $validated['order_id'] ?? null;

        $metadata = array_merge([
            'source'   => 'checkout',
            'order_id' => $orderId,
            'user_id'  => optional($request->user())->id,
        ], $validated['metadata'] ?? []);

        $idempotencyKey = $orderId ? 'checkout_'.$orderId : 'checkout_'.Str::uuid();

        $secret = config('services.stripe.secret');
        if (empty($secret)) {
            Log::error('Stripe secret manquant (services.stripe.secret)');
            return $this->responseJson(false, 'Configuration Stripe manquante', null, 500);
        }
        Stripe::setApiKey($secret);

        try {
            $session = CheckoutSession::create([
                'mode'                       => 'payment',
                'success_url'                => rtrim($validated['success_url'], '/') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'                 => $validated['cancel_url'],
                'customer_email'             => $validated['customer_email'] ?? null,
                'allow_promotion_codes'      => true,
                'billing_address_collection' => 'auto',
                'line_items' => [[
                    'price_data' => [
                        'currency'     => $currency,
                        'unit_amount'  => (int) $validated['amount'],
                        'product_data' => ['name' => 'Paiement DSPay'],
                    ],
                    'quantity' => 1,
                ]],
                'metadata'            => $metadata,
                'payment_intent_data' => ['metadata' => $metadata],
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
                    'metadata'            => array_merge($metadata, ['session_id' => $session->id]),
                    'processed_at'        => null,
                ]
            );

            return $this->responseJson(true, 'Checkout session créée', [
                'id'          => $session->id,
                'url'         => $session->url,
                'amount'      => (int) $validated['amount'],
                'currency'    => $currency,
                'server_mode' => str_starts_with($secret, 'sk_live_') ? 'live' : 'test',
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Stripe Checkout error', ['msg' => $e->getMessage()]);
            return $this->responseJson(false, 'Erreur Stripe : '.$e->getMessage(), null, 422);

        } catch (\Throwable $e) {
            Log::error('CheckoutSession store failed', ['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->responseJson(false, 'Erreur serveur : '.$e->getMessage(), null, 500);
        }
    }
}
