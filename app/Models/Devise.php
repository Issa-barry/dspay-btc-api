<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Devise extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom', 
        'tag'
    ];

     public function setNomAttribute($value)
     {
         $this->attributes['nom'] = ucfirst(strtolower($value));
     }
}
 