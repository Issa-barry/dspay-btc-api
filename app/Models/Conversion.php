<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Conversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'devise_source_id',
        'devise_cible_id',
        'montant_source',
        'montant_converti',
        'taux',
    ];

    public function deviseSource()
    {
        return $this->belongsTo(Devise::class, 'devise_source_id');
    }

    public function deviseCible()
    {
        return $this->belongsTo(Devise::class, 'devise_cible_id');
    }

     // Relation avec le taux de change
     public function tauxEchange()
     {
         return $this->belongsTo(TauxEchange::class, 'devise_source_id', 'devise_source_id');
     }
}
