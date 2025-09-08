<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;

class RoleAssigneController extends Controller
{
    use JsonResponseTrait;

    public function assigneRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|exists:roles,name',
        ]);

        // Récupérer l'utilisateur
        $user = User::find($request->user_id);

        if (!$user) {
            return $this->responseJson(false, 'Utilisateur non trouvé.', null, 404);
        }

        // Assigner le rôle (utilisation de Spatie Permissions)
        try {
            $user->assignRole($request->role);

            return $this->responseJson(true, 'Rôle assigné avec succès à l\'utilisateur.', [
                'user' => $user,
                'role' => $request->role,
            ], 201);
        } catch (\Exception $e) {
            return $this->responseJson(false, 'Erreur lors de l\'assignation du rôle.', $e->getMessage(), 500);
        }
    }
}
