<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentEnLigne extends Model
{
    use HasFactory;

    protected $table = 'payment_en_lignes';

    /**
     * Les champs pouvant être remplis en masse
     */
    protected $fillable = [
        'provider',             // stripe, paypal…
        'provider_payment_id',  // id unique provider (pi_xxx, PAYID-xxx…)
        'status',               // succeeded, failed, pending…
        'amount',               // montant en centimes
        'currency',             // EUR, USD…
        'user_id',              // lien vers l’utilisateur
        'metadata',             // données additionnelles
        'processed_at',         // date de traitement provider
    ];

    /**
     * Casting automatique des colonnes
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

    // Chaque paiement appartient à un utilisateur (facultatif)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ─── Scopes pratiques ───
     */
    public function scopeSucceeded($query)
    {
        return $query->where('status', self::STATUS_SUCCEEDED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
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
}
