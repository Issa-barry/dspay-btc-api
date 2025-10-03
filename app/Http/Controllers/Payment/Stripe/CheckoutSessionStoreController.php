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

    public function store(Request $request)
    {
        // ⚠️ On n'utilise pas $request->validate() pour pouvoir renvoyer notre JSON 422
        $validator = Validator::make($request->all(), [
            'amount'         => 'required|integer|min:50',      // centimes
            'currency'       => 'nullable|string|in:eur,usd',
            'success_url'    => 'required|url',
            'cancel_url'     => 'required|url',
            'customer_email' => 'nullable|email',
            'metadata'       => 'nullable|array',
            'order_id'       => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->responseJson(false, 'Validation error', [
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // ↳ normalisations
        $currency = $validated['currency'] ?? 'eur';
        $success  = rtrim($validated['success_url'], '/');
        $cancel   = rtrim($validated['cancel_url'], '/');

        $metadata = array_merge([
            'source'   => 'checkout',
            'order_id' => $validated['order_id'] ?? null,
        ], $validated['metadata'] ?? []);

        // Idempotence stable
        $idempotencyKey = ($validated['order_id'] ?? false)
            ? 'checkout_' . $validated['order_id']
            : 'checkout_' . Str::uuid();

        $secret = config('services.stripe.secret');
        if (empty($secret)) {
            Log::error('Stripe secret manquant (services.stripe.secret)');
            return $this->responseJson(false, 'Configuration Stripe manquante', null, 500);
        }

        Stripe::setApiKey($secret);

        try {
            $session = CheckoutSession::create([
                'mode'                        => 'payment',
                'success_url'                 => $success . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'                  => $cancel,
                'customer_email'              => $validated['customer_email'] ?? null,
                'allow_promotion_codes'       => true,
                'billing_address_collection'  => 'auto',
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
                    'provider_payment_id' => $session->id, // compat
                    'payment_intent_id'   => null,
                    'status'              => 'pending',
                    'amount'              => (int) $validated['amount'],
                    'currency'            => $currency,
                    'user_id'             => optional($request->user())->id,
                    'metadata'            => [
                        'session_id' => $session->id,
                        'order_id'   => $validated['order_id'] ?? null,
                    ] + $metadata,
                    'processed_at'        => null,
                ]
            );

            return $this->responseJson(true, 'Checkout session created', [
                'id'  => $session->id,
                'url' => $session->url,
            ], 201);

        } catch (ApiErrorException $e) {
            Log::warning('Stripe Checkout error', ['msg' => $e->getMessage()]);
            return $this->responseJson(false, 'Erreur Stripe : ' . $e->getMessage(), null, 422);

        } catch (\Throwable $e) {
            Log::error('CheckoutSession store failed', [
                'msg'   => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->responseJson(false, 'Erreur serveur : ' . $e->getMessage(), null, 500);
        }
    }

    public function show(string $sessionId)
    {
        $pel = PaymentEnLigne::where('session_id', $sessionId)->first();

        if (!$pel) {
            return $this->responseJson(false, 'Session inconnue', null, 404);
        }

        // Si le webhook a terminé, on peut remonter le transfert
        $meta = is_array($pel->metadata) ? $pel->metadata : [];
        $transfert = null;
        if (!empty($meta['transfert_id'])) {
            $transfert = Transfert::with('beneficiaire') // si tu as la relation
                ->find($meta['transfert_id']);
        }

        return $this->responseJson(true, 'Checkout session status', [
            'session_id'   => $pel->session_id,
            'status'       => $pel->status,           // pending | processing | succeeded | failed | canceled | paid
            'amount'       => $pel->amount,           // en centimes
            'currency'     => $pel->currency,
            'processed_at' => $pel->processed_at,
            'metadata'     => $meta,
            'transfert_id' => $meta['transfert_id'] ?? null,
            'transfert'    => $transfert,            // peut être null tant que non finalisé
        ]);
    }
}
