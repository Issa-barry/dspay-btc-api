<?php

namespace App\Http\Controllers\Payment\Stripe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\JsonResponseTrait;

use App\Models\PaymentEnLigne;
use App\Models\Transfert;

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
            return $this->responseJson(false, 'Invalid signature', null, 400);
        }

        try {
            Log::info('Stripe event received', ['type' => $event->type, 'id' => $event->id]);

            // ──────────────────────────────
            // 1) CHECKOUT (page hébergée)
            // ──────────────────────────────
            if ($event->type === 'checkout.session.completed') {
                /** @var \Stripe\Checkout\Session $cs */
                $cs = $event->data->object;

                $pel = PaymentEnLigne::where('session_id', $cs->id)
                    ->orWhere('provider_payment_id', $cs->id)
                    ->first();

                if ($pel) {
                    // Attache PI s'il existe
                    if (!empty($cs->payment_intent) && empty($pel->payment_intent_id)) {
                        $pel->payment_intent_id = (string) $cs->payment_intent;
                    }

                    // Fusion d'un éventuel "twin" (ligne créée par PI)
                    if (!empty($cs->payment_intent)) {
                        $twin = PaymentEnLigne::where('payment_intent_id', (string) $cs->payment_intent)
                            ->where('id', '!=', $pel->id)->first();
                        if ($twin) $this->mergePelTwins($pel, $twin);
                    }

                    $pel->status = $cs->payment_status === 'paid'
                        ? 'succeeded'
                        : (string) $cs->payment_status;

                    $emailFromCheckout = $cs->customer_details?->email
                        ?? $cs->customer_email
                        ?? null;

                    $oldMeta    = is_array($pel->metadata) ? $pel->metadata : [];
                    $metaStripe = $this->toArraySafe($cs->metadata ?? []);
                    $pel->metadata = array_merge($oldMeta, $metaStripe, [
                        'customer_email' => $emailFromCheckout ?? ($oldMeta['customer_email'] ?? null),
                        'last_event'     => $event->type,
                        'livemode'       => (bool) ($cs->livemode ?? false),
                        'source'         => $oldMeta['source'] ?? 'checkout',
                    ]);

                    // ⭐ Sauvegarder AVANT la finalisation
                    $pel->save();

                    if ($cs->payment_status === 'paid' && empty($pel->processed_at)) {
                        try {
                            $ok = $this->finalizeAfterSuccess($pel); // écrit metadata.transfert_id
                            if ($ok) {
                                // Recharger pour récupérer la metadata mise à jour
                                $pel->refresh();
                                $pel->processed_at = now();
                                $pel->save();
                            }
                        } catch (\Throwable $e) {
                            Log::error('Finalize (checkout) failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
                        }
                    }
                } else {
                    Log::warning('Checkout session completed but no local row found', ['session_id' => $cs->id]);
                }

                return new Response('OK', 200);
            }

            // ──────────────────────────────
            // 2) PAYMENT INTENT (Elements & co)
            // ──────────────────────────────
            if (str_starts_with($event->type, 'payment_intent.')) {
                /** @var \Stripe\PaymentIntent $pi */
                $pi = $event->data->object;

                $typeToStatus = [
                    'payment_intent.succeeded'      => 'succeeded',
                    'payment_intent.canceled'       => 'canceled',
                    'payment_intent.payment_failed' => 'failed',
                    'payment_intent.processing'     => 'processing',
                ];
                if (!isset($typeToStatus[$event->type])) {
                    Log::info('PI event ignored', ['type' => $event->type]);
                    return new Response('OK', 200);
                }
                $newStatus = $typeToStatus[$event->type];

                // Essayer d'attacher au Checkout via order_id
                $orderId = $pi->metadata->order_id ?? null;
                $pel = null;
                if ($orderId) {
                    $pel = PaymentEnLigne::where('metadata->order_id', $orderId)->first();
                }

                // Sinon, fallback sur payment_intent_id
                if (!$pel) {
                    $pel = PaymentEnLigne::firstOrCreate(
                        ['payment_intent_id' => (string) $pi->id],
                        [
                            'provider'            => 'stripe',
                            'provider_payment_id' => (string) $pi->id,
                            'status'              => 'pending',
                        ]
                    );
                } else {
                    if (empty($pel->payment_intent_id)) {
                        $pel->payment_intent_id = (string) $pi->id;
                    }
                }

                // Jamais rétrograder
                if ($pel->status === 'succeeded' && $newStatus !== 'succeeded') {
                    Log::info('Skip status demotion', ['current' => $pel->status, 'new' => $newStatus]);
                    return new Response('OK', 200);
                }

                $pel->amount   = (int) ($pi->amount ?? $pel->amount ?? 0);
                $pel->currency = strtolower((string) ($pi->currency ?? $pel->currency ?? 'eur'));
                $pel->status   = $newStatus;

                $metaStripe = [];
                if (isset($pi->metadata) && is_iterable($pi->metadata)) {
                    foreach ($pi->metadata as $k => $v) { $metaStripe[$k] = $v; }
                }

                $oldMeta = is_array($pel->metadata) ? $pel->metadata : [];
                $pel->metadata = array_merge($oldMeta, $metaStripe, [
                    'customer_email' => $oldMeta['customer_email'] ?? null,
                    'last_event'     => $event->type,
                    'livemode'       => (bool) ($pi->livemode ?? false),
                ]);

                // ⭐ Sauvegarder AVANT la finalisation
                $pel->save();

                if ($newStatus === 'succeeded' && empty($pel->processed_at)) {
                    try {
                        $ok = $this->finalizeAfterSuccess($pel);
                        if ($ok) {
                            $pel->refresh();
                            $pel->processed_at = now();
                            $pel->save();
                        }
                    } catch (\Throwable $e) {
                        Log::error('Finalize (PI) failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
                    }
                }

                return new Response('OK', 200);
            }

            Log::info('Stripe event ignored', ['type' => $event->type]);
            return new Response('OK', 200);

        } catch (\Throwable $e) {
            Log::error('Webhook handler error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return new Response('OK', 200); // stop retries Stripe
        }
    }

    /**
     * Rejoue la finalisation (utile pour réparer une session déjà payée).
     */
    public function reprocess(string $sessionId)
    {
        $pel = PaymentEnLigne::where('session_id', $sessionId)->first();
        if (!$pel) return $this->responseJson(false, 'Session inconnue', null, 404);

        $ok = false;
        try {
            $ok = $this->finalizeAfterSuccess($pel);
            if ($ok && empty($pel->processed_at)) {
                $pel->refresh();
                $pel->processed_at = now();
                $pel->save();
            }
        } catch (\Throwable $e) {
            Log::error('Reprocess failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }

        $meta = is_array($pel->metadata) ? $pel->metadata : [];
        return $this->responseJson(true, 'Reprocess terminé', [
            'status'       => $pel->status,
            'processed_at' => $pel->processed_at,
            'transfert_id' => $meta['transfert_id'] ?? null,
        ]);
    }

    /**
     * Finalisation métier avec les NOUVEAUX CHAMPS.
     * 🎯 Appelle le contrôleur TransfertEnvoieController
     */
    protected function finalizeAfterSuccess(PaymentEnLigne $pel): bool
    {
        $meta = is_array($pel->metadata) ? $pel->metadata : [];
        if (!empty($pel->processed_at) || !empty($meta['transfert_id'])) {
            Log::info('Finalize skipped: already processed', [
                'payment_en_ligne_id' => $pel->id,
                'processed_at' => $pel->processed_at,
                'transfert_id' => $meta['transfert_id'] ?? null,
            ]);
            return true; // déjà traité
        }

        $created = false;

        DB::transaction(function () use ($pel, &$created) {
            /** @var PaymentEnLigne $locked */
            $locked = PaymentEnLigne::whereKey($pel->id)->lockForUpdate()->first();
            $lockedMeta = is_array($locked->metadata) ? $locked->metadata : [];

            if (!empty($locked->processed_at) || !empty($lockedMeta['transfert_id'])) {
                $created = true;
                return;
            }

            // ⭐ Extraction des metadata avec les NOUVEAUX champs
            $beneficiaireId = isset($lockedMeta['beneficiaire_id']) ? (int) $lockedMeta['beneficiaire_id'] : null;
            $tauxId         = isset($lockedMeta['taux_echange_id']) ? (int) $lockedMeta['taux_echange_id'] : null;
            $montantEuro    = isset($lockedMeta['montant_envoie']) ? (float) $lockedMeta['montant_envoie'] : null;
            $serviceId      = $lockedMeta['serviceId'] ?? Transfert::SERVICE_ORANGE_MONEY;
            $userId         = $locked->user_id ?: (isset($lockedMeta['user_id']) ? (int) $lockedMeta['user_id'] : null);

            Log::info('Finalize: Processing payment', [
                'payment_en_ligne_id' => $locked->id,
                'beneficiaire_id' => $beneficiaireId,
                'taux_id' => $tauxId,
                'montant_euro' => $montantEuro,
                'serviceId' => $serviceId,
                'user_id' => $userId,
                'recipientTel' => $lockedMeta['recipientTel'] ?? null,
                'accountId' => $lockedMeta['accountId'] ?? null,
            ]);

            if (!$beneficiaireId || !$tauxId || $montantEuro === null) {
                Log::warning('Finalize skipped: missing required metadata', [
                    'payment_en_ligne_id' => $locked->id,
                    'meta' => $lockedMeta,
                ]);
                return;
            }

            // ⭐ Préparer la requête simulée pour TransfertEnvoieController
            $fakeRequest = Request::create('/api/transferts', 'POST', [
                'beneficiaire_id' => $beneficiaireId,
                'taux_echange_id' => $tauxId,
                'montant_envoie'  => $montantEuro,
                'serviceId'       => $serviceId,
                'recipientTel'    => $lockedMeta['recipientTel'] ?? null,
                'accountId'       => $lockedMeta['accountId'] ?? null,
                'customerPhoneNumber' => $lockedMeta['customerPhoneNumber'] ?? null,
            ]);

            // ⭐ Simuler l'utilisateur authentifié
            if ($userId) {
                $user = \App\Models\User::find($userId);
                if ($user) {
                    $fakeRequest->setUserResolver(fn() => $user);
                    \Illuminate\Support\Facades\Auth::setUser($user);
                    
                    Log::info('User authenticated for webhook transfer', [
                        'user_id' => $userId,
                        'user_email' => $user->email,
                    ]);
                } else {
                    Log::error('User not found', [
                        'user_id' => $userId,
                    ]);
                    return;
                }
            } else {
                Log::error('No user_id in payment metadata', [
                    'payment_en_ligne_id' => $locked->id,
                ]);
                return;
            }

            // ⭐ Appeler le contrôleur TransfertEnvoieController
            try {
                $controller = app(\App\Http\Controllers\Transfert\TransfertEnvoieController::class);
                
                Log::info('Calling TransfertEnvoieController from webhook', [
                    'data' => $fakeRequest->all(),
                    'user_id' => $userId,
                ]);
                
                $response = $controller->store($fakeRequest);

                // Vérifier la réponse
                $responseData = json_decode($response->getContent(), true);
                
                Log::info('TransfertEnvoieController response', [
                    'status_code' => $response->getStatusCode(),
                    'response_data' => $responseData,
                ]);
                
                if (isset($responseData['success']) && $responseData['success'] === true) {
                    $transfertData = $responseData['data'] ?? null;
                    
                    if ($transfertData && isset($transfertData['id'])) {
                        // Mise à jour des metadata avec le transfert_id
                        $lockedMeta['transfert_id'] = $transfertData['id'];
                        $locked->metadata = $lockedMeta;
                        $locked->save();
                        
                        $created = true;
                        
                        Log::info('✅ Transfert créé depuis webhook via contrôleur', [
                            'transfert_id' => $transfertData['id'],
                            'payment_en_ligne_id' => $locked->id,
                            'code' => $transfertData['code'] ?? null,
                            'serviceId' => $transfertData['serviceId'] ?? null,
                        ]);
                    } else {
                        Log::error('Transfert créé mais ID manquant dans la réponse', [
                            'response' => $responseData,
                        ]);
                    }
                } else {
                    Log::error('❌ Échec création transfert depuis webhook', [
                        'response' => $responseData,
                        'payment_en_ligne_id' => $locked->id,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('💥 Exception lors de l\'appel du contrôleur TransfertEnvoieController', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'payment_en_ligne_id' => $locked->id,
                ]);
            }
        });

        return $created;
    }

    // ────────────────────────────── Helpers ──────────────────────────────

    private function mergePelTwins(PaymentEnLigne $target, PaymentEnLigne $source): void
    {
        if ($target->id === $source->id) return;

        $metaT = is_array($target->metadata) ? $target->metadata : [];
        $metaS = is_array($source->metadata) ? $source->metadata : [];
        $target->metadata = array_replace($metaS, $metaT); // priorité au target

        if (!$target->payment_intent_id && $source->payment_intent_id) $target->payment_intent_id = $source->payment_intent_id;
        if (!$target->amount && $source->amount) $target->amount = $source->amount;
        if (!$target->currency && $source->currency) $target->currency = $source->currency;
        if ($source->status === 'succeeded') $target->status = 'succeeded';
        if (!$target->processed_at && $source->processed_at) $target->processed_at = $source->processed_at;

        $target->save();
    }

    private function toArraySafe($meta): array
    {
        if (is_array($meta)) return $meta;
        if (is_object($meta) && method_exists($meta, 'toArray')) return $meta->toArray();
        if (is_string($meta)) {
            $j = json_decode($meta, true);
            return is_array($j) ? $j : [];
        }
        return [];
    }
}