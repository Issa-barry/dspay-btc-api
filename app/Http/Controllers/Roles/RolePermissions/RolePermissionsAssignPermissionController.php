<?php

namespace App\Http\Controllers\Roles\RolePermissions;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class RolePermissionsAssignPermissionController extends Controller
{
    /**
     * Assigner une permission à un utilisateur.
     *
     * @param Request $request
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignPermission(Request $request, $userId)
    {
        $request->validate([
            'permission' => 'required|exists:permissions,name',
        ]);

        $user = User::findOrFail($userId);
        $user->givePermissionTo($request->permission);

        return response()->json([
            'message' => "La permission {$request->permission} a été assignée à l'utilisateur."
        ]);
    }

     /**
     * Assigner une ou plusieurs permissions à un rôle.
     *
     * @param Request $request
     * @param int $roleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignPermissionsToRole(Request $request, $roleId)
    {
        // Valider les permissions envoyées
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name',  // chaque permission doit exister dans la table permissions
        ]);

        // Trouver le rôle par ID
        $role = Role::findOrFail($roleId);

        // Assigner les permissions au rôle
        $role->givePermissionTo($request->permissions);

        return response()->json([
            'message' => "Les permissions ont été assignées au rôle {$role->name}.",
        ]);
    }

}
