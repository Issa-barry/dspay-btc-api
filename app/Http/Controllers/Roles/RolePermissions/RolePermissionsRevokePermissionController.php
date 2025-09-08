<?php

namespace App\Http\Controllers\Roles\RolePermissions;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class RolePermissionsRevokePermissionController extends Controller
{
      /**
     * Retirer une permission d'un utilisateur.
     *
     * @param Request $request
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function revokePermission(Request $request, $userId)
    {
        $request->validate([
            'permission' => 'required|exists:permissions,name',
        ]);

        $user = User::findOrFail($userId);
        $user->revokePermissionTo($request->permission);

        return response()->json([
            'message' => "La permission {$request->permission} a été retirée de l'utilisateur."
        ]);
    }

    
     /**
     * Retirer une permission d'un rôle.
     *
     * @param Request $request
     * @param int $roleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function revokePermissionFromRole(Request $request, $roleId)
    {
        // Valider les permissions envoyées
        $request->validate([
            'permission' => 'required|exists:permissions,name',
        ]);

        // Trouver le rôle par ID
        $role = Role::findOrFail($roleId);

        // Retirer la permission du rôle
        $role->revokePermissionTo($request->permission);

        return response()->json([
            'message' => "La permission {$request->permission} a été retirée du rôle {$role->name}.",
        ]);
    }
}
