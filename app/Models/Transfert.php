<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'beneficiaire_id',
        'devise_source_id',
        'devise_cible_id',
        'taux_echange_id',
        'taux_applique',      // ENTIER (ex: 10700)
        'montant_envoie',    // DECIMAL(15,2)
        'frais',            // DECIMAL(10,2) — frais en €
        'total_ttc',       // DECIMAL(12,2) — montant_envoie + frais
        'montant_gnf',    // ENTIER — montant reçu
        'total_gnf',     // ENTIER — = montant_gnf (pas de frais en GNF)
        'code',
        'statut',
    ];

    protected $casts = [
        'montant_envoie'  => 'decimal:2',
        'frais'     => 'decimal:2',
        'total_ttc'     => 'decimal:2',
        'taux_applique' => 'integer',
        'montant_gnf'   => 'integer',
        'total_gnf'     => 'integer',
    ];

    /* =======================
     |  Mutateurs (GNF = int)
     =======================*/
    public function setMontantGnfAttribute($value): void
    {
        $this->attributes['montant_gnf'] = (int) round((float) $value, 0, PHP_ROUND_HALF_UP);
    }

    public function setTotalGnfAttribute($value): void
    {
        $this->attributes['total_gnf'] = (int) round((float) $value, 0, PHP_ROUND_HALF_UP);
    }

    /* ============
     |  Relations
     ============*/
    public function expediteur()   { return $this->belongsTo(User::class, 'user_id'); }
    public function beneficiaire() { return $this->belongsTo(Beneficiaire::class, 'beneficiaire_id'); }
    public function deviseSource() { return $this->belongsTo(Devise::class, 'devise_source_id'); }
    public function deviseCible()  { return $this->belongsTo(Devise::class, 'devise_cible_id'); }
    public function tauxEchange()  { return $this->belongsTo(TauxEchange::class, 'taux_echange_id'); }

    /* =========
     | Helpers
     =========*/
    /** Conversion EUR->GNF avec taux entier, arrondi entier. */
    public function calculerMontantConverti(): int
    {
        return (int) round(((float) $this->montant_envoie) * ((int) $this->taux_applique), 0, PHP_ROUND_HALF_UP);
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
            $t->code ??= self::generateUniqueCode();
            $t->devise_source_id ??= 1; // EUR
            $t->devise_cible_id  ??= 2; // GNF

            // Si taux non fourni mais lien présent => snapshot ENTIER
            if ((!$t->taux_applique && $t->relationLoaded('tauxEchange')) || $t->taux_echange_id) {
                $taux = $t->tauxEchange()->value('taux');
                if ($taux !== null) {
                    $t->taux_applique = (int) $taux;
                }
            }
        });
    }
}
