<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Traits\JsonResponseTrait;
use Exception;
use Spatie\Permission\Models\Role;

class RoleDeleteController extends Controller
{
    use JsonResponseTrait;

    /**
     * Supprimer un rôle par son ID.
     */
    public function destroy($id)
    {
        try {
            $role = Role::findOrFail($id);
 
            // Dissocier les permissions liées avant suppression
            $role->permissions()->detach();

            // Supprimer le rôle
            $role->delete();

            return $this->responseJson(true, 'Rôle supprimé avec succès.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->responseJson(false, 'Rôle introuvable.', null, 404);
        } catch (\Exception $e) {
            return $this->responseJson(false, 'Une erreur est survenue lors de la suppression du rôle.', $e->getMessage(), 500);
        }
    }
}
