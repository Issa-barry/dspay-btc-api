<?php

namespace App\Http\Controllers\Payment\Stripe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\JsonResponseTrait;
use App\Models\PaymentEnLigne;

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
            // Pour Stripe, renvoie un 400 simple. Si tu appelles cette route à la main, tu as un JSON propre.
            return $this->responseJson(false, 'Invalid signature', null, 400);
        }

        try {
            Log::info('Stripe event', ['type' => $event->type, 'id' => $event->id]);

            if ($event->type === 'payment_intent.succeeded' || $event->type === 'payment_intent.payment_failed') {
                /** @var \Stripe\PaymentIntent $pi */
                $pi = $event->data->object;

                $pel = PaymentEnLigne::firstOrCreate(
                    ['payment_intent_id' => $pi->id],
                    ['provider' => 'stripe', 'provider_payment_id' => $pi->id]
                );

                $map = [
                    'succeeded'                => 'succeeded',
                    'processing'               => 'processing',
                    'canceled'                 => 'canceled',
                    'requires_payment_method'  => 'pending',
                    'requires_action'          => 'pending',
                    'requires_confirmation'    => 'pending',
                ];

                $pel->amount   = (int) ($pi->amount ?? 0);
                $pel->currency = (string) ($pi->currency ?? 'eur');
                $pel->status   = $map[$pi->status] ?? 'pending';
                $meta = is_array($pel->metadata) ? $pel->metadata : [];
                $pel->metadata = array_merge($meta, [
                    'last_event' => $event->type,
                    'livemode'   => (bool) ($pi->livemode ?? false),
                ]);

                if ($pi->status === 'succeeded' && empty($pel->processed_at)) {
                    // place ta logique “finalize” ici si les metadata nécessaires sont présentes
                    $pel->processed_at = now();
                }

                $pel->save();
            } else {
                Log::info('Event ignored', ['type' => $event->type]);
            }
        } catch (\Throwable $e) {
            Log::error('Webhook handler error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            // Stripe exige 2xx pour arrêter les retries
            return new Response('OK', 200);
        }

        // Stripe s’attend juste à un 2xx rapide
        return new Response('OK', 200);
    }
}
