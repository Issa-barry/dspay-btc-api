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
            // Stripe attend un 4xx en cas de signature invalide.
            return $this->responseJson(false, 'Invalid signature', null, 400);
        }

        try {
            Log::info('Stripe event received', ['type' => $event->type, 'id' => $event->id]);

            /**
             * 1) CHECKOUT (page hébergée)
             *    Relie la session Checkout à notre enregistrement et met à jour le statut.
             */
            if ($event->type === 'checkout.session.completed') {
                /** @var \Stripe\Checkout\Session $cs */
                $cs = $event->data->object;

                // On retrouve la ligne créée lors de la création de la session (clé naturelle = session_id)
                $pel = PaymentEnLigne::where('session_id', $cs->id)
                    ->orWhere('provider_payment_id', $cs->id) // compat si tu avais stocké ici la cs_*
                    ->first();

                if ($pel) {
                    // Remonter l'id du PI rattaché à la session
                    if (!empty($cs->payment_intent) && empty($pel->payment_intent_id)) {
                        $pel->payment_intent_id = (string) $cs->payment_intent;
                    }

                    // Payment status côté Checkout: paid / unpaid / no_payment_required
                    $pel->status = $cs->payment_status === 'paid' ? 'succeeded' : (string) $cs->payment_status;

                    $oldMeta      = is_array($pel->metadata) ? $pel->metadata : [];
                    $pel->metadata = array_merge($oldMeta, [
                        'last_event' => $event->type,
                        'livemode'   => (bool) ($cs->livemode ?? false),
                    ]);

                    if ($cs->payment_status === 'paid' && empty($pel->processed_at)) {
                        $this->finalizeAfterSuccess($pel);
                        $pel->processed_at = now();
                    }

                    $pel->save();
                } else {
                    Log::warning('Checkout session completed but no local row found', ['session_id' => $cs->id]);
                }

                return new Response('OK', 200);
            }

            /**
             * 2) PAYMENT INTENT (Elements et signaux complémentaires de Checkout)
             */
            if (str_starts_with($event->type, 'payment_intent.')) {
                /** @var \Stripe\PaymentIntent $pi */
                $pi = $event->data->object;

                // Map Stripe -> notre modèle
                $map = [
                    'succeeded'                 => 'succeeded',
                    'processing'                => 'processing',
                    'canceled'                  => 'canceled',
                    'requires_payment_method'   => 'pending',
                    'requires_action'           => 'pending',
                    'requires_confirmation'     => 'pending',
                ];

                // Pour Elements: on retrouve/crée par payment_intent_id
                $pel = PaymentEnLigne::firstOrCreate(
                    ['payment_intent_id' => (string) $pi->id],
                    [
                        'provider'            => 'stripe',
                        'provider_payment_id' => (string) $pi->id,
                        'status'              => 'pending',
                    ]
                );

                $pel->amount   = (int) ($pi->amount ?? $pel->amount ?? 0);
                $pel->currency = (string) ($pi->currency ?? $pel->currency ?? 'eur');
                $pel->status   = $map[$pi->status] ?? 'pending';

                $oldMeta       = is_array($pel->metadata) ? $pel->metadata : [];
                $pel->metadata = array_merge($oldMeta, [
                    'last_event' => $event->type,
                    'livemode'   => (bool) ($pi->livemode ?? false),
                ]);

                if ($pi->status === 'succeeded' && empty($pel->processed_at)) {
                    $this->finalizeAfterSuccess($pel);
                    $pel->processed_at = now();
                }

                $pel->save();

                return new Response('OK', 200);
            }

            // Autres événements non utilisés : on log et on renvoie OK
            Log::info('Stripe event ignored', ['type' => $event->type]);
            return new Response('OK', 200);

        } catch (\Throwable $e) {
            Log::error('Webhook handler error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            // IMPORTANT: Stripe attend un 2xx pour stopper les retries.
            return new Response('OK', 200);
        }
    }

    /**
     * Place ici ta logique métier de finalisation (idempotente) :
     * - créer le Transfert à partir des metadata (beneficiaire_id, montant, etc.)
     * - générer facture / envoyer email
     * - ne rien faire si déjà traité (processed_at non null, ou verrou idempotent)
     */
    protected function finalizeAfterSuccess(PaymentEnLigne $pel): void
    {
        // Exemple (pseudo):
        // if (isset($pel->metadata['transfert_created'])) return;
        // ... créer le transfert / facture ...
        // $meta = $pel->metadata ?? [];
        // $meta['transfert_created'] = true;
        // $pel->metadata = $meta;
        // (sauvegarde décalée à l’appelant)
    }
}
