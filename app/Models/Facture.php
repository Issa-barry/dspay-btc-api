<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfert_id',
        'type',
        'statut',
        'envoye',
        'nom_societe',
        'adresse_societe',
        'phone_societe',
        'email_societe',
        'total',
        'montant_du'
    ];

    protected $attributes = [
        'statut' => 'brouillon',
        'envoye' => false,
    ];

    public function transfert()
    {
        return $this->belongsTo(Transfert::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function updateMontantDu()
    {
        $totalPaiements = $this->payments->sum('montant');
        $this->montant_du = $this->total - $totalPaiements;
        $this->save();
    }
}
