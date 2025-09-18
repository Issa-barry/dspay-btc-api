<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TauxEchange;
use App\Models\Devise;

class Transfert extends Model
{ 
    use HasFactory;

    protected $fillable = [
        'user_id',
        'beneficiaire_id',
        'devise_source_id',
        'devise_cible_id',
        'taux_echange_id',
        'taux_applique',
        'montant_euro',
        'montant_gnf',
        'frais',
        'total',
        'code',
        'statut',
    ];

    /* Relations */
    public function expediteur()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function beneficiaire()
    {
        return $this->belongsTo(Beneficiaire::class, 'beneficiaire_id');
    }

    public function deviseSource()
    {
        return $this->belongsTo(Devise::class, 'devise_source_id');
    }

    public function deviseCible()
    {
        return $this->belongsTo(Devise::class, 'devise_cible_id');
    }

    public function tauxEchange()
    {
        return $this->belongsTo(TauxEchange::class, 'taux_echange_id');
    }

    /* Helpers */
    public function calculerMontantConverti(): float
    {
        // snapshot du taux
        return (float) $this->montant_euro * (float) $this->taux_applique;
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 2)) . rand(1000, 9999);
        } while (self::where('code', $code)->exists());

        return $code;
    }

    protected static function booted()
    {
        static::creating(function (Transfert $t) {
            // code unique
            $t->code ??= self::generateUniqueCode();

            // valeurs par défaut des devises
            $t->devise_source_id ??= 1; // EUR
            $t->devise_cible_id  ??= 2; // GNF

            // si pas de taux_applique fourni mais un taux_echange_id est présent
            if (!$t->taux_applique && $t->relationLoaded('tauxEchange') || $t->taux_echange_id) {
                $taux = $t->tauxEchange()->value('taux');
                if ($taux) {
                    $t->taux_applique = $taux;
                }
            }
        });
    }
}
