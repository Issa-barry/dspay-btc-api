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
        } catch (\Exception $e) {
            Log::warning('Stripe webhook invalid: '.$e->getMessage());
            return response('Invalid', 400);
        }

        $object = $event->data->object ?? null;

        if (!$object || empty($object->id)) {
            Log::warning('Stripe webhook sans objet valide');
            return response('Invalid payload', 400);
        }

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->updatePayment($object, 'succeeded');
                break;

            case 'payment_intent.payment_failed':
                $this->updatePayment($object, 'failed');
                break;

            case 'payment_intent.canceled':
                $this->updatePayment($object, 'canceled');
                break;

            case 'charge.refunded':
                $this->updatePayment($object->payment_intent ?? null, 'refunded');
                break;

            default:
                Log::info("ℹ️ Event Stripe ignoré : {$event->type}");
                break;
        }

        return response('ok', 200);
    }

    /**
     * Met à jour la table payment_en_lignes
     */
    protected function updatePayment($object, string $status)
    {
        $paymentId = is_string($object) ? $object : $object->id;

        if (!$paymentId) {
            Log::warning("⚠️ Impossible de mettre à jour : ID Stripe manquant");
            return;
        }

        $payment = PaymentEnLigne::where('provider_payment_id', $paymentId)->first();

        if ($payment) {
            $payment->update([
                'status'       => $status,
                'processed_at' => now(),
                'metadata'     => array_merge($payment->metadata ?? [], [
                    'last_event' => $status,
                ]),
            ]);

            Log::info("✅ Paiement mis à jour", [
                'provider_payment_id' => $paymentId,
                'status'              => $status,
            ]);
        } else {
            Log::warning("⚠️ Paiement introuvable pour Stripe ID : {$paymentId}");
        }
    }
}
