<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use URL;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    protected $fillable = [
        'email', 'password', 'reference', 'civilite', 'nom', 'prenom',
        'phone', 'dial_code', 'date_naissance', 'role_id', 'statut',
        'pays', 'country_code', 'adresse', 'complement_adresse', 'ville', 'quartier', 'region', 'code_postal',
        'verification_code', 'verification_code_expires_at',
    ];

    protected $appends = ['nom_complet'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verification_code_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * 🌍 Mapping country_code → dial_code
     */
    public static function getCountryDialCodes(): array
    {
        return [
            // France et DOM-TOM
            'FR' => '+33',  'YT' => '+262', 'RE' => '+262', 'GP' => '+590',
            'MQ' => '+596', 'GF' => '+594', 'MF' => '+590', 'BL' => '+590',
            'PM' => '+508', 'WF' => '+681', 'NC' => '+687', 'PF' => '+689',
            
            // Europe
            'GR' => '+30',  'NL' => '+31',  'BE' => '+32',  'ES' => '+34',
            'HU' => '+36',  'IT' => '+39',  'RO' => '+40',  'CH' => '+41',
            'AT' => '+43',  'GB' => '+44',  'JE' => '+44',  'GG' => '+44',
            'DK' => '+45',  'SE' => '+46',  'PL' => '+48',  'DE' => '+49',
            'PT' => '+351', 'LU' => '+352', 'IE' => '+353', 'MT' => '+356',
            'CY' => '+357', 'FI' => '+358', 'BG' => '+359', 'LT' => '+370',
            'LV' => '+371', 'EE' => '+372', 'HR' => '+385', 'CZ' => '+420',
            'SK' => '+421',
            
            // Afrique francophone
            'GN' => '+224', 'SN' => '+221', 'ML' => '+223', 'CI' => '+225',
        ];
    }

    /**
     * 📱 Configuration des formats de téléphone par pays
     */
    private static function getPhoneConfigs(): array
    {
        return [
            'FR' => ['dialCode' => '+33',  'length' => 9,  'startsWithZero' => true],
            'YT' => ['dialCode' => '+262', 'length' => 9,  'startsWithZero' => true],
            'RE' => ['dialCode' => '+262', 'length' => 9,  'startsWithZero' => true],
            'GP' => ['dialCode' => '+590', 'length' => 9,  'startsWithZero' => true],
            'MQ' => ['dialCode' => '+596', 'length' => 9,  'startsWithZero' => true],
            'GF' => ['dialCode' => '+594', 'length' => 9,  'startsWithZero' => true],
            'MF' => ['dialCode' => '+590', 'length' => 9,  'startsWithZero' => true],
            'BL' => ['dialCode' => '+590', 'length' => 9,  'startsWithZero' => true],
            'PM' => ['dialCode' => '+508', 'length' => 6,  'startsWithZero' => false],
            'WF' => ['dialCode' => '+681', 'length' => 6,  'startsWithZero' => false],
            'NC' => ['dialCode' => '+687', 'length' => 6,  'startsWithZero' => false],
            'PF' => ['dialCode' => '+689', 'length' => 6,  'startsWithZero' => false],
            'GR' => ['dialCode' => '+30',  'length' => 10, 'startsWithZero' => false],
            'NL' => ['dialCode' => '+31',  'length' => 9,  'startsWithZero' => true],
            'BE' => ['dialCode' => '+32',  'length' => 9,  'startsWithZero' => true],
            'ES' => ['dialCode' => '+34',  'length' => 9,  'startsWithZero' => false],
            'HU' => ['dialCode' => '+36',  'length' => 9,  'startsWithZero' => false],
            'IT' => ['dialCode' => '+39',  'length' => 10, 'startsWithZero' => true],
            'RO' => ['dialCode' => '+40',  'length' => 9,  'startsWithZero' => true],
            'CH' => ['dialCode' => '+41',  'length' => 9,  'startsWithZero' => true],
            'AT' => ['dialCode' => '+43',  'length' => 10, 'startsWithZero' => true],
            'GB' => ['dialCode' => '+44',  'length' => 10, 'startsWithZero' => true],
            'JE' => ['dialCode' => '+44',  'length' => 10, 'startsWithZero' => true],
            'GG' => ['dialCode' => '+44',  'length' => 10, 'startsWithZero' => true],
            'DK' => ['dialCode' => '+45',  'length' => 8,  'startsWithZero' => false],
            'SE' => ['dialCode' => '+46',  'length' => 9,  'startsWithZero' => true],
            'PL' => ['dialCode' => '+48',  'length' => 9,  'startsWithZero' => false],
            'DE' => ['dialCode' => '+49',  'length' => 10, 'startsWithZero' => true],
            'PT' => ['dialCode' => '+351', 'length' => 9,  'startsWithZero' => false],
            'LU' => ['dialCode' => '+352', 'length' => 9,  'startsWithZero' => false],
            'IE' => ['dialCode' => '+353', 'length' => 9,  'startsWithZero' => true],
            'MT' => ['dialCode' => '+356', 'length' => 8,  'startsWithZero' => false],
            'CY' => ['dialCode' => '+357', 'length' => 8,  'startsWithZero' => false],
            'FI' => ['dialCode' => '+358', 'length' => 9,  'startsWithZero' => true],
            'BG' => ['dialCode' => '+359', 'length' => 9,  'startsWithZero' => false],
            'LT' => ['dialCode' => '+370', 'length' => 8,  'startsWithZero' => false],
            'LV' => ['dialCode' => '+371', 'length' => 8,  'startsWithZero' => false],
            'EE' => ['dialCode' => '+372', 'length' => 7,  'startsWithZero' => false],
            'HR' => ['dialCode' => '+385', 'length' => 9,  'startsWithZero' => true],
            'CZ' => ['dialCode' => '+420', 'length' => 9,  'startsWithZero' => false],
            'SK' => ['dialCode' => '+421', 'length' => 9,  'startsWithZero' => false],
            'GN' => ['dialCode' => '+224', 'length' => 9,  'startsWithZero' => false],
            'SN' => ['dialCode' => '+221', 'length' => 9,  'startsWithZero' => false],
            'ML' => ['dialCode' => '+223', 'length' => 8,  'startsWithZero' => false],
            'CI' => ['dialCode' => '+225', 'length' => 10, 'startsWithZero' => false],
        ];
    }

    /**
     * ✅ Mutator country_code : Auto-définir le dial_code
     */
    protected function countryCode(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value,
            set: function (?string $value) {
                if ($value) {
                    $value = strtoupper($value);
                    // ✅ Auto-définir le dial_code selon le country_code
                    $dialCodes = self::getCountryDialCodes();
                    if (isset($dialCodes[$value])) {
                        $this->attributes['dial_code'] = $dialCodes[$value];
                    }
                }
                return $value;
            }
        );
    }

    /**
     * ✅ Mutator phone : Normaliser automatiquement
     */
    protected function phone(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value,
            set: function (?string $value) {
                if (!$value) return null;
                
                $cleanPhone = preg_replace('/[^\d+]/', '', $value);
                
                if (str_starts_with($cleanPhone, '+')) {
                    return $cleanPhone;
                }
                
                if ($this->country_code) {
                    return $this->normalizeWithCountryCode($cleanPhone);
                }
                
                return $this->autoDetectAndNormalize($cleanPhone);
            }
        );
    }

    /**
     * Normaliser avec le country_code
     */
    private function normalizeWithCountryCode(string $phone): string
    {
        $configs = self::getPhoneConfigs();
        $config = $configs[$this->country_code] ?? null;
        
        if (!$config) {
            $dialCodes = self::getCountryDialCodes();
            $dialCode = $dialCodes[$this->country_code] ?? '';
            return $dialCode . ltrim($phone, '0');
        }
        
        if ($config['startsWithZero'] && str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }
        
        return $config['dialCode'] . $phone;
    }

    /**
     * Auto-détection du pays
     */
    private function autoDetectAndNormalize(string $phone): string
    {
        $length = strlen($phone);
        $firstDigit = $phone[0] ?? '';
        
        if ($length === 10 && $firstDigit === '0') {
            return '+33' . substr($phone, 1);
        }
        
        if ($length === 9 && in_array($firstDigit, ['6', '7'])) {
            return '+33' . $phone;
        }
        
        if ($length === 9 && $firstDigit === '6') {
            return '+224' . $phone;
        }
        
        if ($length === 9 && $firstDigit === '7') {
            return '+221' . $phone;
        }
        
        if ($length === 8) {
            return '+223' . $phone;
        }
        
        if ($length === 10 && !str_starts_with($phone, '0')) {
            return '+225' . $phone;
        }
        
        return $phone;
    }

    /**
     * ✅ Rechercher par téléphone ET country_code (distinguer les doublons)
     */
    public static function findByPhone(string $phone, ?string $countryCode = null): ?self
    {
        $normalizedPhone = self::normalizePhone($phone, null, $countryCode);
        
        $query = self::where('phone', $normalizedPhone);
        
        // ✅ Si country_code fourni, filtrer aussi par ça (important pour +262)
        if ($countryCode) {
            $query->where('country_code', strtoupper($countryCode));
        }
        
        return $query->first();
    }

    /**
     * ✅ Normaliser un téléphone
     */
    public static function normalizePhone(string $phone, ?string $dialCode = null, ?string $countryCode = null): string
    {
        $cleanPhone = preg_replace('/[^\d+]/', '', $phone);
        
        if (str_starts_with($cleanPhone, '+')) {
            return $cleanPhone;
        }
        
        // Utiliser country_code en priorité
        if ($countryCode) {
            $configs = self::getPhoneConfigs();
            $config = $configs[strtoupper($countryCode)] ?? null;
            
            if ($config) {
                if ($config['startsWithZero'] && str_starts_with($cleanPhone, '0')) {
                    $cleanPhone = substr($cleanPhone, 1);
                }
                return $config['dialCode'] . $cleanPhone;
            }
        }
        
        // Sinon utiliser dial_code
        if ($dialCode) {
            return $dialCode . ltrim($cleanPhone, '0');
        }
        
        // Auto-détection
        return (new self)->autoDetectAndNormalize($cleanPhone);
    }

    /**
     * Téléphone sans dial_code
     */
    public function getPhoneNationalAttribute(): string
    {
        if (!$this->phone || !$this->dial_code) {
            return $this->phone ?? '';
        }
        return str_replace($this->dial_code, '', $this->phone);
    }

    /**
     * Téléphone formaté
     */
    public function getPhoneFormattedAttribute(): string
    {
        if (!$this->phone) return '';
        
        $national = $this->phone_national;
        
        if (in_array($this->country_code, ['FR', 'BE', 'NL', 'CH'])) {
            return preg_replace('/(\d{1})(\d{2})(\d{2})(\d{2})(\d{2})/', '$1 $2 $3 $4 $5', $national);
        }
        
        if ($this->country_code === 'GN') {
            return preg_replace('/(\d{3})(\d{2})(\d{2})(\d{2})/', '$1 $2 $3 $4', $national);
        }
        
        return preg_replace('/(\d{2})/', '$1 ', trim($national));
    }

    public function getNomCompletAttribute(): string
    {
        return trim(implode(' ', array_filter([$this->prenom, $this->nom])));
    }

    public function role()
    {
        return $this->roles()->first();
    }

    protected static function booted()
    {
        static::creating(function ($user) {
            $user->reference = self::generateUniqueReference();
        });
    }

    public static function generateUniqueReference(): string
    {
        do {
            $reference = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 2))
                . rand(10, 99) . rand(0, 9);
        } while (self::where('reference', $reference)->exists());
        return $reference;
    }

    public function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
        );
    }

    /**
     * Générer un code de vérification à 4 chiffres
     */
    public function generateVerificationCode(): string
    {
        $code = str_pad((string)rand(0, 9999), 4, '0', STR_PAD_LEFT);

        $this->verification_code = $code;
        $this->verification_code_expires_at = now()->addMinutes(15); // Expire après 15 minutes
        $this->save();

        return $code;
    }

    /**
     * Vérifier si le code de vérification est valide
     */
    public function verifyCode(string $code): bool
    {
        if ($this->verification_code !== $code) {
            return false;
        }

        if ($this->verification_code_expires_at && $this->verification_code_expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Marquer l'email comme vérifié et supprimer le code
     */
    public function markEmailAsVerifiedWithCode(): void
    {
        $this->markEmailAsVerified();
        $this->verification_code = null;
        $this->verification_code_expires_at = null;
        $this->save();
    }
}