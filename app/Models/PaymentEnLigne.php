<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\PaymentEnLigne
 *
 * @property int|null    $id
 * @property string|null $provider            // ex: 'stripe'
 * @property string|null $provider_payment_id // id générique (pi_..., cs_...)
 * @property string|null $session_id          // Checkout session (cs_...)
 * @property string|null $payment_intent_id   // PaymentIntent (pi_...)
 * @property string      $status              // pending|succeeded|failed|refunded|processing|canceled
 * @property int|null    $amount              // centimes
 * @property string      $currency            // 'eur' par défaut
 * @property int|null    $user_id
 * @property array|null  $metadata
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read float $amount_decimal
 */
class PaymentEnLigne extends Model
{
    use HasFactory;

    /** Table explicite */
    protected $table = 'payment_en_lignes';

    /** Champs remplissables */
    protected $fillable = [
        'provider',
        'provider_payment_id',
        'session_id',
        'payment_intent_id',
        'status',
        'amount',
        'currency',
        'user_id',
        'metadata',
        'processed_at',
    ];

    /** Casts */
    protected $casts = [
        'metadata'     => 'array',
        'processed_at' => 'datetime',
    ];

    /** Valeurs par défaut au niveau modèle (en plus de la DB si définie) */
    protected $attributes = [
        'status'   => self::STATUS_PENDING,
        'currency' => 'eur',
    ];

    /** Attributs dérivés retournés dans toArray()/JSON */
    protected $appends = ['amount_decimal'];

    // ─────────────────────────────────────────────────────────────────────
    // Statuts internes
    // ─────────────────────────────────────────────────────────────────────
    public const STATUS_SUCCEEDED  = 'succeeded';
    public const STATUS_PENDING    = 'pending';
    public const STATUS_FAILED     = 'failed';
    public const STATUS_REFUNDED   = 'refunded';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_CANCELED   = 'canceled';

    public const STATUSES = [
        self::STATUS_SUCCEEDED,
        self::STATUS_PENDING,
        self::STATUS_FAILED,
        self::STATUS_REFUNDED,
        self::STATUS_PROCESSING,
        self::STATUS_CANCELED,
    ];

    // ─────────────────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────────────
    public function scopeSucceeded($q) { return $q->where('status', self::STATUS_SUCCEEDED); }
    public function scopeFailed($q)    { return $q->where('status', self::STATUS_FAILED); }
    public function scopePending($q)   { return $q->where('status', self::STATUS_PENDING); }
    public function scopeRecent($q)    { return $q->orderByDesc('created_at'); }

    public function scopeBySession($q, string $sessionId)
    {
        return $q->where('session_id', $sessionId);
    }

    public function scopeByPaymentIntent($q, string $piId)
    {
        return $q->where('payment_intent_id', $piId);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────
    public function isSucceeded(): bool  { return $this->status === self::STATUS_SUCCEEDED; }
    public function isFailed(): bool     { return $this->status === self::STATUS_FAILED; }
    public function isPending(): bool    { return $this->status === self::STATUS_PENDING; }
    public function isProcessing(): bool { return $this->status === self::STATUS_PROCESSING; }
    public function isCanceled(): bool   { return $this->status === self::STATUS_CANCELED; }

    /** Montant en devise (si `amount` est en centimes) */
    public function getAmountDecimalAttribute(): float
    {
        return (int)($this->amount ?? 0) / 100;
    }

    /** Marque la ligne comme traitée (idempotent) */
    public function markProcessed(): self
    {
        if (empty($this->processed_at)) {
            $this->processed_at = now();
            $this->save();
        }
        return $this;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Stripe helpers
    // ─────────────────────────────────────────────────────────────────────

    /** Mappe un statut Stripe (PaymentIntent) vers le statut interne */
    public static function mapStripeStatus(?string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'succeeded'                => self::STATUS_SUCCEEDED,
            'processing'               => self::STATUS_PROCESSING,
            'canceled'                 => self::STATUS_CANCELED,
            'requires_payment_method',
            'requires_action',
            'requires_confirmation',
            null                       => self::STATUS_PENDING,
            default                    => self::STATUS_PENDING,
        };
    }

    /**
     * Créé/MàJ une ligne à partir d’un \Stripe\PaymentIntent
     * - Idempotent (basé sur payment_intent_id)
     * - Fusionne les metadata existantes + Stripe + extra
     */
    public static function upsertFromPaymentIntent(\Stripe\PaymentIntent $pi, ?int $userId = null, array $extraMeta = []): self
    {
        $pel = self::firstOrCreate(
            ['payment_intent_id' => $pi->id],
            [
                'provider'            => 'stripe',
                'provider_payment_id' => $pi->id,
                'user_id'             => $userId,
            ]
        );

        $pel->amount   = (int) ($pi->amount ?? 0);
        $pel->currency = strtolower((string) ($pi->currency ?? 'eur'));
        $pel->status   = self::mapStripeStatus($pi->status);

        // Stripe\StripeObject -> array
        $stripeMeta = [];
        if (isset($pi->metadata) && is_iterable($pi->metadata)) {
            foreach ($pi->metadata as $k => $v) { $stripeMeta[$k] = $v; }
        }

        $current = is_array($pel->metadata) ? $pel->metadata : [];
        $pel->metadata = array_merge($current, $stripeMeta, $extraMeta, [
            'last_event' => 'payment_intent.'.$pi->status,
            'livemode'   => (bool) ($pi->livemode ?? false),
        ]);

        $pel->save();

        return $pel;
    }
}
