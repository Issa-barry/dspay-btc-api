<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TauxEchange extends Model
{
    use HasFactory;

    protected $fillable = [
        'devise_source_id',
        'devise_cible_id',
        'taux',
    ];

    // Relation avec le modèle Devise (devise source)
    public function deviseSource()
    {
        return $this->belongsTo(Devise::class, 'devise_source_id');
    }

    // Relation avec le modèle Devise (devise cible)
    public function deviseCible()
    {
        return $this->belongsTo(Devise::class, 'devise_cible_id');
    }
}
