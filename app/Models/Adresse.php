<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Adresse extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pays',
        'adresse',
        'complement_adresse',
        'ville',
        'code_postal',
        'quartier',    
        'region', 
        'code',
    ];

    /**
     * Relation : une adresse appartient à un utilisateur.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : une adresse peut être liée à plusieurs agences.
     */
    public function agences()
    {
        return $this->hasMany(Agence::class);
    }
}
