<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;

class RoleListeUsersDuRoleController extends Controller
{
    use JsonResponseTrait;

    /**
     * Vérifie si un rôle est assigné à des utilisateurs.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkRoleUsers($id)
    {
        try {
            $role = Role::with('users')->find($id);

            if (!$role) {
                return $this->responseJson(false, 'Rôle introuvable.', null, 404);
            }

            if ($role->users->isNotEmpty()) {
                return $this->responseJson(false, 'Ce rôle est assigné à des utilisateurs.', [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'users_count' => $role->users->count(),
                    'users' => $role->users->pluck('id', 'name') // Renvoie l'ID et le nom des utilisateurs
                ], 400);
            }

            return $this->responseJson(true, 'Ce rôle n\'est pas assigné à des utilisateurs.', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'users_count' => 0
            ]);
        } catch (\Exception $e) {
            return $this->responseJson(false, 'Une erreur est survenue.', $e->getMessage(), 500);
        }
    }
}
