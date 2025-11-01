<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Depot extends Model
{
    use HasFactory;

    protected $fillable = [
        'serviceId',
        'amount',
        'recipientTel',
        'accountId',
        'customerPhoneNumber',
        'status',
        'transaction_ref',
    ];

    /**
     * Génère automatiquement la référence du dépôt.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($depot) {
            $today = now()->format('Ymd'); // ex: 20251101

            // Compte combien de dépôts ont été faits aujourd’hui
            $countToday = self::whereDate('created_at', now()->toDateString())->count() + 1;

            // Formate avec 4 chiffres (0001, 0002, ...)
            $increment = str_pad($countToday, 4, '0', STR_PAD_LEFT);

            // Génère la référence complète
            $depot->transaction_ref = "DSP-{$today}-{$increment}";
        });
    }
}
