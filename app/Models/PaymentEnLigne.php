<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentEnLigne extends Model
{
    use HasFactory;

    protected $table = 'payment_en_lignes';

    /**
     * Champs remplissables
     */
    protected $fillable = [
        'provider',              // stripe, paypal…
        'provider_payment_id',   // compat: id générique (peut contenir cs_ ou pi_)
        'session_id',            // Stripe Checkout session (cs_...)
        'payment_intent_id',     // Stripe PaymentIntent (pi_...)
        'status',                // succeeded, failed, pending, refunded…
        'amount',                // centimes
        'currency',              // EUR, USD…
        'user_id',               // user lié (nullable)
        'metadata',              // JSON
        'processed_at',          // datetime traitée
    ];

    /**
     * Casts
     */
    protected $casts = [
        'metadata'     => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * ─── Constantes de statuts ───
     */
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_PENDING   = 'pending';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_REFUNDED  = 'refunded';

    public const STATUSES = [
        self::STATUS_SUCCEEDED,
        self::STATUS_PENDING,
        self::STATUS_FAILED,
        self::STATUS_REFUNDED,
    ];

    /**
     * ─── Relations ───
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ─── Scopes ───
     */
    public function scopeSucceeded($query)
    {
        return $query->where('status', self::STATUS_SUCCEEDED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeByPaymentIntent($query, string $piId)
    {
        return $query->where('payment_intent_id', $piId);
    }

    /**
     * ─── Helpers ───
     */
    public function isSucceeded(): bool
    {
        return $this->status === self::STATUS_SUCCEEDED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    // Accesseur pratique: montant en unité (€, $…)
    public function getAmountDecimalAttribute(): float
    {
        return $this->amount / 100;
    }
}
