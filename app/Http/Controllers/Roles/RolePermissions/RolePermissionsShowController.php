<?php

namespace App\Http\Controllers\Roles\RolePermissions;

use App\Http\Controllers\Controller;
use App\Traits\JsonResponseTrait;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class RolePermissionsShowController extends Controller
{
    use JsonResponseTrait;

    /**
     * Liste tous les rôles et leurs permissions associées.
     */
    public function listRolesPermissions()
    {
        try {
            $roles = Role::with('permissions')->get();
            $permissions = Permission::all()->groupBy('model_type');

            return $this->responseJson(true, 'Liste des rôles et permissions récupérée avec succès.', [
                'roles' => $roles,
                'permissions' => $permissions,
            ]);
        } catch (\Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la récupération des rôles et permissions.', $e->getMessage(), 500);
        }
    }

    /**
     * Récupère les permissions d'un rôle spécifique.
     *
     * @param int $roleId
     */
    public function getRolePermissions($roleId)
    {
        try {
            // Vérifier que l'ID est un nombre valide
            if (!is_numeric($roleId)) {
                return $this->responseJson(false, 'ID du rôle invalide.', null, 400);
            }

            $role = Role::with('permissions')->find($roleId);

            if (!$role) {
                return $this->responseJson(false, 'Rôle introuvable.', null, 404);
            }

            return $this->responseJson(true, "Les permissions du rôle {$role->name} ont été récupérées avec succès.", [
                'role' => $role,
                'permissions' => $role->permissions->groupBy('model_type'), 
            ]);
        } catch (\Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la récupération des permissions du rôle.', $e->getMessage(), 500);
        }
    }
}
