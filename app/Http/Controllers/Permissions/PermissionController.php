<?php

namespace App\Http\Controllers\Permissions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Fonction pour centraliser les réponses JSON
     */
    protected function responseJson($success, $message, $data = null, $statusCode = 200)
    {
        return response()->json([
            'success' => $success, 
            'message' => $message,
            'data' => $data
        ], $statusCode);
    } 

    /**
     * Crée une nouvelle permission avec un modèle spécifié.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'model_type' => 'required|string|max:255',  // Ajout du modèle
        ]);

        try {
            $permission = Permission::create([
                'name' => $validated['name'],
                'guard_name' => 'web',  // Utiliser le guard 'web'
                'model_type' => $validated['model_type'], // Enregistrer le modèle spécifié
            ]);

            return $this->responseJson(true, 'Permission créée avec succès.', $permission, 201);
        } catch (\Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la création de la permission.', null, 500);
        }
    }

    /**
     * Récupère toutes les permissions organisées par modèle.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Récupérer toutes les permissions et les organiser par modèle
        // $permissions = Permission::all()->groupBy('model_type');

        // return $this->responseJson(true, 'Liste des permissions récupérée avec succès.', $permissions, 200);
        $permissions = Permission::all()->groupBy('model_type');
     
        return response()->json([
            'success' => true,
            'message' => 'Liste des rôles et permissions récupérée avec succès.',
            'data' =>  $permissions,
        ], 200);
    }

    /**
     * Affiche une permission spécifique.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return $this->responseJson(false, 'Permission introuvable.', null, 404);
        }

        return $this->responseJson(true, 'Permission récupérée avec succès.', $permission, 200);
    }

    /**
     * Met à jour une permission existante avec un modèle optionnel.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $id,
            'model_type' => 'sometimes|required|string|max:255',  // Modèle optionnel pour la mise à jour
        ]);

        $permission = Permission::find($id);

        if (!$permission) {
            return $this->responseJson(false, 'Permission introuvable.', null, 404);
        }

        // Mettre à jour les champs de permission
        $permission->name = $request->name;

        // Mettre à jour le modèle si fourni
        if ($request->has('model_type')) {
            $permission->model_type = $request->model_type;
        }

        $permission->save();

        return $this->responseJson(true, 'Permission mise à jour avec succès.', $permission, 200);
    }

    /**
     * Supprime une permission existante.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return $this->responseJson(false, 'Permission introuvable.', null, 404);
        }

        $permission->delete();

        return $this->responseJson(true, 'Permission supprimée avec succès.', null, 200);
    }
}