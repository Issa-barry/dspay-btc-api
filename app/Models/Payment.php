<?php

// app/Models/Payment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'facture_id',
        'type',
        'montant'
    ];

    public function facture()
    {
        return $this->belongsTo(Facture::class);
    }
}
