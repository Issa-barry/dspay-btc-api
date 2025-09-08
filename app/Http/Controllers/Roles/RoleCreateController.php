<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RoleCreateController extends Controller
{
    /**
     * Créer un nouveau rôle
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Validation des données d'entrée
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:roles,name',
            ]);

            // Mettre la première lettre en majuscule
            $roleName = ucfirst(strtolower($validated['name']));

            // Création du rôle
            $role = Role::create([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rôle créé avec succès.',
                'data' => $role
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la création du rôle.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
