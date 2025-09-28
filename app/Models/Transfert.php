<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfert extends Model
{
    use HasFactory;

    private const CODE_PREFIX = 'DSP-'; // ← Tous les codes commencent par DSP

    // --- Statuts possibles ---
    public const STATUT_ENVOYE = 'envoyé';
    public const STATUT_RETIRE = 'retiré';
    public const STATUT_ANNULE = 'annulé';
    public const STATUT_BLOQUE = 'bloqué';

    public const STATUTS = [
        self::STATUT_ENVOYE,
        self::STATUT_RETIRE,
        self::STATUT_ANNULE,
        self::STATUT_BLOQUE,
    ];

    // --- Modes d’envoi ---
    public const MODE_ORANGE_MONEY = 'orange_money';
    public const MODE_EWALLET      = 'ewallet';
    public const MODE_RETRAIT_CASH = 'retrait_cash';

    public const MODES_RECEPTION = [
        self::MODE_ORANGE_MONEY,
        self::MODE_EWALLET,
        self::MODE_RETRAIT_CASH,
    ];

    protected $fillable = [
        'user_id',
        'beneficiaire_id',
        'devise_source_id',
        'devise_cible_id',
        'taux_echange_id',
        'taux_applique',      // ENTIER (ex: 10700)
        'montant_envoie',     // DECIMAL(15,2)
        'frais',              // DECIMAL(10,2) — frais en €
        'total_ttc',          // DECIMAL(12,2) — montant_envoie + frais
        'montant_gnf',        // ENTIER — montant reçu
        'total_gnf',          // ENTIER — = montant_gnf (pas de frais en GNF)
        'code',
        'statut',
        'mode_reception',
    ];

    protected $casts = [
        'montant_envoie' => 'decimal:2',
        'frais'          => 'decimal:2',
        'total_ttc'      => 'decimal:2',
        'taux_applique'  => 'integer',
        'montant_gnf'    => 'integer',
        'total_gnf'      => 'integer',
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

    /* ============ Relations ============*/
    public function expediteur()   { return $this->belongsTo(User::class, 'user_id'); }
    public function beneficiaire() { return $this->belongsTo(Beneficiaire::class, 'beneficiaire_id'); }
    public function deviseSource() { return $this->belongsTo(Devise::class, 'devise_source_id'); }
    public function deviseCible()  { return $this->belongsTo(Devise::class, 'devise_cible_id'); }
    public function tauxEchange()  { return $this->belongsTo(TauxEchange::class, 'taux_echange_id'); }

    /* ============== Scopes ==============*/
    public function scopeMode($query, string $mode)
    {
        return $query->where('mode_reception', $mode);
    }

    public function scopeStatut($query, string $statut)
    {
        return $query->where('statut', $statut);
    }

    /* ============= Helpers =============*/
    /** Conversion EUR->GNF avec taux entier, arrondi entier. */
    public function calculerMontantConverti(): int
    {
        return (int) round(((float) $this->montant_envoie) * ((int) $this->taux_applique), 0, PHP_ROUND_HALF_UP);
    }

    /** Génère un code unique au format DSP + 2 lettres + 4 chiffres (ex: DSPAB1234) */
    public static function generateUniqueCode(): string
    {
        do {
            $letters = self::randomLetters(2);          // AB
            $digits  = random_int(1000, 9999);          // 1234
            $code    = self::CODE_PREFIX . $letters . $digits; // DSPAB1234
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /** Lettres uniquement (sans I/O confus) */
    private static function randomLetters(int $length): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $out;
    }

    protected static function booted()
    {
        static::creating(function (Transfert $t) {
            // Code toujours présent et bien préfixé
            if (empty($t->code) || !str_starts_with($t->code, self::CODE_PREFIX)) {
                $t->code = self::generateUniqueCode();
            }

            $t->devise_source_id ??= 1; // EUR
            $t->devise_cible_id  ??= 2; // GNF
            $t->mode_reception   ??= self::MODE_RETRAIT_CASH;

            // Snapshot du taux ENTIER si fourni via relation ou ID
            if ((!$t->taux_applique && $t->relationLoaded('tauxEchange')) || $t->taux_echange_id) {
                $taux = $t->tauxEchange()->value('taux');
                if ($taux !== null) {
                    $t->taux_applique = (int) $taux;
                }
            }
        });
    }
}
