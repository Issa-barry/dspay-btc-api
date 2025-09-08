<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Permission;

class Role extends SpatieRole
{
    use HasFactory;

    protected $fillable = [
        'name',
        'guard_name'
    ];

     /**
     * Mutator pour capitaliser la première lettre du nom du rôle
     */
    public function setNameAttribute($value)
    {
         $this->attributes['name'] = ucfirst(strtolower($value));
    }

     public function permissions(): BelongsToMany
    {
        return parent::permissions(); // Hérite de la relation définie dans SpatieRole
    }

    /**
     * Relation avec les utilisateurs (BelongsToMany).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user', 'role_id', 'user_id');
    }

    /**
     * Vérifie si le rôle est associé à des utilisateurs.
     */
    public function hasUsers(): bool
    {
        return $this->users()->exists();
    }
    
    // public static function boot()
    //     {
    //         parent::boot();

    //         static::deleting(function ($role) {
    //             // Supprimer les relations utilisateur-rôle dans la table pivot
    //             $role->users()->detach();

    //             // Supprimer les permissions liées
    //             $role->permissions()->detach();
    //         });
    //     }

}
