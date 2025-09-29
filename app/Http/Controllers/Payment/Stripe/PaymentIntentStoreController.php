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
            'amount'   => 'required|integer|min:50',
            'currency' => 'nullable|string|in:eur,usd,xof',
            'metadata' => 'array',
            'order_id' => 'nullable|string|max:100',
        ]);

        try {
            $currency = $validated['currency'] ?? 'eur';
            $metadata = array_merge([
                'source'   => 'payment_intent',
                'order_id' => $validated['order_id'] ?? null,
            ], $validated['metadata'] ?? []);

            // Idempotency key
            $idempotencyKey = $validated['order_id']
                ? 'pi_'.$validated['order_id']
                : 'pi_'.Str::uuid();

            Stripe::setApiKey(config('services.stripe.secret'));

            $intent = PaymentIntent::create([
                'amount'                    => $validated['amount'],
                'currency'                  => $currency,
                'automatic_payment_methods' => ['enabled' => true],
                'metadata'                  => $metadata,
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);

            // Trace en base
            PaymentEnLigne::updateOrCreate(
                ['provider_payment_id' => $intent->id],
                [
                    'provider'     => 'stripe',
                    'status'       => 'pending',
                    'amount'       => (int) $validated['amount'],
                    'currency'     => $currency,
                    'user_id'      => optional($request->user())->id,
                    'metadata'     => $metadata,
                    'processed_at' => null,
                ]
            );

            return $this->responseJson(true, 'PaymentIntent créé avec succès', [
                'clientSecret' => $intent->client_secret,
                'id'           => $intent->id,
                'status'       => $intent->status,
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
