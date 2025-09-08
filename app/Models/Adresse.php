<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Adresse extends Model
{
    use HasFactory;

    protected $fillable = [
        'pays',
        'adresse',
        'complement_adresse',
        'ville',
        'code_postal',
        'quartier',    
        'region', 
    ];

    public function agences()
    {
        return $this->hasMany(Agence::class);
    }
}
