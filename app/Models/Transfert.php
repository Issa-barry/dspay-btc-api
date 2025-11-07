<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfert extends Model
{
    use HasFactory;

    private const CODE_PREFIX = 'DSP-';

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

    // --- Services de réception (renommé de MODES_RECEPTION) ---
    public const SERVICE_ORANGE_MONEY = 'orange_money';
    public const SERVICE_KS_PAY = 'ks_pay';
    public const SERVICE_PAYCARD = 'paycard';
    public const SERVICE_SOUTRAT_MONEY = 'soutrat_money';
    public const SERVICE_KULU = 'kulu';
    public const SERVICE_MOMO = 'momo';

    public const SERVICES = [
        self::SERVICE_ORANGE_MONEY,
        self::SERVICE_KS_PAY,
        self::SERVICE_PAYCARD,
        self::SERVICE_SOUTRAT_MONEY,
        self::SERVICE_KULU,
        self::SERVICE_MOMO,
    ];

    protected $fillable = [
        'user_id',
        'beneficiaire_id',
        'devise_source_id',
        'devise_cible_id',
        'taux_echange_id',
        'taux_applique',
        'montant_envoie',
        'frais',
        'total_ttc',
        'montant_gnf',
        'total_gnf',
        'code',
        'statut',
        'serviceId', // ← Renommé
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
    public function scopeService($query, string $service)
    {
        return $query->where('serviceId', $service);
    }

    public function scopeStatut($query, string $statut)
    {
        return $query->where('statut', $statut);
    }

    /* ============= Helpers =============*/
    public function calculerMontantConverti(): int
    {
        return (int) round(((float) $this->montant_envoie) * ((int) $this->taux_applique), 0, PHP_ROUND_HALF_UP);
    }

    public static function generateUniqueCode(): string
    {
        do {
            $letters = self::randomLetters(2);
            $digits  = random_int(1000, 9999);
            $code    = self::CODE_PREFIX . $letters . $digits;
        } while (self::where('code', $code)->exists());

        return $code;
    }

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
            if (empty($t->code) || !str_starts_with($t->code, self::CODE_PREFIX)) {
                $t->code = self::generateUniqueCode();
            }

            $t->devise_source_id ??= 1;
            $t->devise_cible_id  ??= 2;
            $t->serviceId ??= self::SERVICE_ORANGE_MONEY; // ← Renommé

            if ((!$t->taux_applique && $t->relationLoaded('tauxEchange')) || $t->taux_echange_id) {
                $taux = $t->tauxEchange()->value('taux');
                if ($taux !== null) {
                    $t->taux_applique = (int) $taux;
                }
            }
        });
    }
}