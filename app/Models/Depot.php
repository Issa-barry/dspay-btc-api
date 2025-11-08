<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Depot extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'serviceId',
        'montant_envoye',
        'amount',
        'recipientTel',
        'accountId',
        'customerPhoneNumber',
        'fieldName',
        'status',
        'transaction_ref',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($depot) {
            if (empty($depot->transaction_ref)) {
                $today = now()->format('Ymd');
                $countToday = self::whereDate('created_at', now()->toDateString())->count() + 1;
                $increment = str_pad($countToday, 4, '0', STR_PAD_LEFT);
                $depot->transaction_ref = "DSP-{$today}-{$increment}";
            }
        });
    }
}
