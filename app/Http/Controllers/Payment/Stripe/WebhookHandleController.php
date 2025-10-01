<?php

namespace App\Http\Controllers\Payment\Stripe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use App\Models\PaymentEnLigne;

class WebhookHandleController extends Controller
{
    public function handle(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            $event = $secret
                ? Webhook::constructEvent($payload, $sigHeader, $secret)
                : json_decode($payload, false);
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook invalid: '.$e->getMessage());
            return response('Invalid', 400);
        }

        $type   = $event->type ?? 'unknown';
        $object = $event->data->object ?? null;

        switch ($type) {
            // ✅ Lien session ↔ PI + maj statut
            case 'checkout.session.completed':
            case 'checkout.session.async_payment_succeeded':
            case 'checkout.session.async_payment_failed':
                $this->handleCheckoutSession($object);
                break;

            // ✅ Événements PaymentIntent
            case 'payment_intent.processing':
                $this->updateByPaymentIntent($object, 'processing');
                break;
            case 'payment_intent.succeeded':
                $this->updateByPaymentIntent($object, 'succeeded');
                break;
            case 'payment_intent.payment_failed':
                $this->updateByPaymentIntent($object, 'failed');
                break;
            case 'payment_intent.canceled':
                $this->updateByPaymentIntent($object, 'canceled');
                break;

            // ✅ Remboursement via Charge
            case 'charge.refunded':
                $piId = $object->payment_intent ?? null;
                if ($piId) {
                    $this->updateByPaymentIntentId($piId, 'refunded');
                } else {
                    Log::warning('charge.refunded sans payment_intent');
                }
                break;

            default:
                Log::info("ℹ️ Event Stripe ignoré : {$type}");
                break;
        }

        return response('ok', 200);
    }

    protected function handleCheckoutSession($session): void
    {
        if (!$session || empty($session->id)) {
            Log::warning('checkout.session.* sans session valide');
            return;
        }

        $status = match($session->payment_status ?? null) {
            'paid' => 'succeeded',
            'no_payment_required' => 'succeeded',
            'unpaid' => 'pending',
            default => 'pending',
        };

        // On cherche d'abord par session_id (cs_...)
        $payment = PaymentEnLigne::where('session_id', $session->id)->first()
            // fallback compat si historique dans provider_payment_id
            ?? PaymentEnLigne::where('provider_payment_id', $session->id)->first();

        if (!$payment) {
            Log::warning("⚠️ Paiement introuvable (session_id={$session->id})");
            return;
        }

        $updates = [
            'status'       => $status,
            'processed_at' => $status === 'succeeded' ? now() : $payment->processed_at,
        ];

        // Lie le PI si présent
        if (!empty($session->payment_intent)) {
            $updates['payment_intent_id'] = is_string($session->payment_intent)
                ? $session->payment_intent
                : ($session->payment_intent->id ?? null);
        }

        $meta = $payment->metadata ?? [];
        $meta['last_event'] = 'checkout.session';
        $meta['raw_status'] = $session->payment_status ?? null;

        $payment->update($updates + ['metadata' => $meta]);

        Log::info('✅ checkout.session traité', [
            'session_id' => $session->id,
            'payment_intent_id' => $updates['payment_intent_id'] ?? null,
            'status' => $updates['status'],
        ]);
    }

    protected function updateByPaymentIntent($pi, string $status): void
    {
        if (!$pi || empty($pi->id)) {
            Log::warning('PI event sans id');
            return;
        }
        $this->updateByPaymentIntentId($pi->id, $status);
    }

    protected function updateByPaymentIntentId(string $piId, string $status): void
    {
        // Recherche normale
        $payment = PaymentEnLigne::where('payment_intent_id', $piId)->first();

        // Fallbacks (compat/historique)
        if (!$payment) {
            $payment = PaymentEnLigne::where('provider_payment_id', $piId)->first()
                    ?? PaymentEnLigne::where('metadata->payment_intent_id', $piId)->first();
        }

        if (!$payment) {
            Log::warning("⚠️ Paiement introuvable pour PI : {$piId}");
            return;
        }

        $meta = $payment->metadata ?? [];
        $meta['last_event'] = $status;

        $payment->update([
            'payment_intent_id' => $piId,
            'status'            => $status,
            'processed_at'      => in_array($status, ['succeeded','refunded'], true) ? now() : $payment->processed_at,
            'metadata'          => $meta,
        ]);

        Log::info('✅ Paiement mis à jour par PI', [
            'payment_intent_id' => $piId,
            'status' => $status,
        ]);
    }
}
