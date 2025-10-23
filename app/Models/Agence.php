<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Agence extends Model
{
     use HasFactory;

    protected $fillable = [
        'nom', 
        'phone',
        'email',
        'statut',
        'pays',
        'ville',
        'quartier',
        // 'reference' n'est PAS fillable : on la génère automatiquement
    ];

    protected static function booted()
    {
        static::creating(function (Agence $agence) {
            if (empty($agence->reference)) {
                $agence->reference = self::generateUniqueReference();
            }
        });
    }

    public static function generateUniqueReference(): string
    {
        do {
            $letters   = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 2));
            $digits    = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $reference = $letters.$digits;
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }
}
