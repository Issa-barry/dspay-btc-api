<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use URL;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;


    /** 
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // App/Models/User.php
    protected $fillable = [
        'email',
        'password',
        'reference',
        'civilite',
        'nom',
        'prenom',
        'phone',
        'date_naissance',
        'role_id',
    ];
 

    // Expose le nom complet en lecture seule (virtuel) pour compatibilité
    protected $appends = ['nom_complet'];

    public function getNomCompletAttribute(): string
    {
        return trim(implode(' ', array_filter([$this->prenom, $this->nom])));
    }

    public function role()
    {
        return $this->roles()->first(); // Retourne le premier rôle associé
    }

    public function adresse()
    {
        return $this->hasOne(Adresse::class);
    }


    // public function adresse()
    // {
    //     return $this->belongsTo(Adresse::class);
    // }

    // public function agence()
    // {
    //     return $this->belongsTo(Agence::class);
    // }

    // Relation to include role data
    // public function getRoleAttribute()
    // {
    //     return $this->roles->pluck('name')->first(); // Return the first role name as string
    // }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    //Genereation de referecen
    protected static function booted()
    {
        static::creating(function ($user) {
            $user->reference = self::generateUniqueReference();
        });

        static::deleting(function ($user) {
            // Supprime l'adresse associée si elle existe
            if ($user->adresse) {
                $user->adresse->delete();
            }
        });
    }

    public static function generateUniqueReference()
    {
        do {
            $reference = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 2)) . rand(10, 99) . rand(0, 9);
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    /** 
     * Get the email verification URL for the given user.
     *
     * @return string
     */
    public function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(1), // Lien expire après 1 minute
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
        );
    }
}
