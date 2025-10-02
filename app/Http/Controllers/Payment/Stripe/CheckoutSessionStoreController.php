<?php

namespace App\Http\Controllers\Payment\Stripe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Stripe\Checkout\Session as CheckoutSession;
use App\Models\PaymentEnLigne;

class CheckoutSessionStoreController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount'         => 'required|integer|min:50',      // centimes
            'currency'       => 'nullable|string|in:eur,usd',
            'success_url'    => 'required|url',
            'cancel_url'     => 'required|url',
            'customer_email' => 'nullable|email',
            'metadata'       => 'array',
            'order_id'       => 'nullable|string|max:100',
        ]);

        $currency   = $validated['currency'] ?? 'eur';
        $metadata   = $validated['metadata'] ?? [];
        $orderId    = $validated['order_id'] ?? null;

        $metadata = array_merge([
            'source'   => 'checkout',
            'order_id' => $orderId,
        ], $metadata);

        $idempotencyKey = $orderId ? 'checkout_'.$orderId : 'checkout_'.Str::uuid();

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = CheckoutSession::create([
            'mode'           => 'payment',
            'success_url'    => $validated['success_url'] . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'     => $validated['cancel_url'],
            'customer_email' => $validated['customer_email'] ?? null,
            'allow_promotion_codes' => true,
            'billing_address_collection' => 'auto',
            'line_items' => [[
                'price_data' => [
                    'currency'     => $currency,
                    'unit_amount'  => $validated['amount'],
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

        $userId = optional($request->user())->id;

        // 🔑 On enregistre par session_id (clé naturelle), et on stocke aussi provider_payment_id pour compat
        PaymentEnLigne::updateOrCreate(
            ['session_id' => $session->id],
            [
                'provider'            => 'stripe',
                'provider_payment_id' => $session->id, // compat (cs_...)
                'payment_intent_id'   => null,         // sera mis par le webhook
                'status'              => 'pending',
                'amount'              => (int) $validated['amount'],
                'currency'            => $currency,
                'user_id'             => $userId,
                'metadata'            => [
                    'session_id' => $session->id,
                    'order_id'   => $orderId,
                ] + $metadata,
                'processed_at'        => null,
            ]
        );

        return response()->json([
            'id'  => $session->id,
            'url' => $session->url,
        ]);
    }
}
