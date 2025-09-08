<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Frais extends Model
{
    use HasFactory;

    protected $table = 'frais';

    protected $fillable = [
        'nom', // Nom du type de frais
        'type', // Pourcentage ou fixe
        'valeur', // Valeur du frais
        'montant_min',
        'montant_max'
    ];
} 