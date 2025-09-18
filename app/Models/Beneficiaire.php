<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Beneficiaire extends Model
{
   use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'nom',
        'prenom',
        'phone',
    ];

    protected $appends = ['nom_complet'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getNomCompletAttribute(): string
    {
        return trim("{$this->prenom} {$this->nom}");
    }
}
