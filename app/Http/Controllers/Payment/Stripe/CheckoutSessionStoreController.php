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
            'order_id'       => 'nullable|string|max:100',      // pour idempotency/traçabilité
        ]);

        $currency   = $validated['currency'] ?? 'eur';
        $metadata   = $validated['metadata'] ?? [];
        $orderId    = $validated['order_id'] ?? null;

        // Hydrate quelques métadatas utiles si non fournies
        $metadata = array_merge([
            'source'   => 'checkout',
            'order_id' => $orderId,
        ], $metadata);

        // Clé d'idempotence : si tu as un order_id coté métier, utilise-le
        $idempotencyKey = $orderId ? 'checkout_'.$orderId : 'checkout_'.Str::uuid();

        Stripe::setApiKey(config('services.stripe.secret'));

        // Création de la session Checkout
        $session = CheckoutSession::create([
            'mode'           => 'payment',
            'success_url'    => $validated['success_url'] . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'     => $validated['cancel_url'],
            'customer_email' => $validated['customer_email'] ?? null,
            // Promotion codes si tu veux permettre des codes promos Stripe
            'allow_promotion_codes' => true,
            // Adresse de facturation si besoin (auto/required)
            'billing_address_collection' => 'auto',
            'line_items' => [[
                'price_data' => [
                    'currency'     => $currency,
                    'unit_amount'  => $validated['amount'],
                    'product_data' => [
                        'name' => 'Paiement DSPay',
                        // 'description' => 'Description optionnelle',
                    ],
                ],
                'quantity' => 1,
            ]],
            // Passe les métadatas à la session
            'metadata' => $metadata,
            // Et aussi au PaymentIntent sous-jacent généré par Checkout
            'payment_intent_data' => [
                'metadata' => $metadata,
            ],
        ], [
            'idempotency_key' => $idempotencyKey,
        ]);

        // Enregistrer immédiatement une trace "pending" — mise à jour par le webhook ensuite
        $userId = optional($request->user())->id;

        PaymentEnLigne::updateOrCreate(
            ['provider_payment_id' => $session->id],
            [
                'provider' => 'stripe',
                'status'   => 'pending',
                'amount'   => (int) $validated['amount'],
                'currency' => $currency,
                'user_id'  => $userId,
                'metadata' => [
                    'session_id' => $session->id,
                    'order_id'   => $orderId,
                ] + $metadata,
                'processed_at' => null,
            ]
        );

        return response()->json([
            'id'  => $session->id,
            'url' => $session->url,
        ]);
    }
}
