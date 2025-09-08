<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TauxEchange;
use App\Models\Devise;

class Transfert extends Model
{
    use HasFactory;

    protected $fillable = [
        'devise_source_id',
        'devise_cible_id',
        'taux_echange_id',
        'montant_expediteur',
        'montant_receveur',
        'receveur_nom_complet',
        'receveur_phone',
        'expediteur_nom_complet',
        'expediteur_phone',
        'expediteur_email',
        'quartier',
        'code',
        'frais',
        'total',
        'statut', 
        'agent_id' 
    ]; 

        public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }


     public function facture()
     {
         return $this->hasOne(Facture::class);
     }

    public function deviseSource()
    {
        return $this->belongsTo(Devise::class, 'devise_source_id');
    }
 
    public function deviseCible()
    {
        return $this->belongsTo(Devise::class, 'devise_cible_id');
    }
 
    public function tauxEchange()
    {
        return $this->belongsTo(TauxEchange::class, 'taux_echange_id');
    }
 
    public function calculerMontantConverti()
    {
        if ($this->tauxEchange) {
            return $this->montant * $this->tauxEchange->taux;
        }

        return 0; // Si aucun taux n'est trouvé, retourner 0
    }
 
     public static function generateUniqueCode()
     {
         do { 
             $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 2)) . rand(1000, 9999);
         } while (self::where('code', $code)->exists());  // Vérifie que le code n'existe pas déjà
 
         return $code;
     }

      // Pour gérer la génération automatique du code lors de la création d'un transfert
    protected static function booted()
    {
        static::creating(function ($transfert) {
            $transfert->code = self::generateUniqueCode();
        });
    }
}
