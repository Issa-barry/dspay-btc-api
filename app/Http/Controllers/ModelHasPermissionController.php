<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class ModelHasPermissionController extends Controller
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
 // Assigner une permission à un modèle
 public function assignPermissionToModel(Request $request)
 {
     $request->validate([
         'permission_id' => 'required|exists:permissions,id',
         'model_type' => 'required|string',
         'model_id' => 'required|integer',
     ]);

     // Vérifier si la permission existe déjà
     $exists = DB::table('model_has_permissions')
         ->where('permission_id', $request->permission_id)
         ->where('model_type', $request->model_type)
         ->where('model_id', $request->model_id)
         ->exists();

     if ($exists) {
         return response()->json([
             'message' => 'La permission est déjà assignée à ce modèle.',
         ], 400);
     }

     // Ajouter la permission
     DB::table('model_has_permissions')->insert([
         'permission_id' => $request->permission_id,
         'model_type' => $request->model_type,
         'model_id' => $request->model_id,
     ]);

     return response()->json([
         'message' => 'Permission assignée avec succès.',
     ]);
 }

 // Lire les permissions d'un modèle
 public function getModelPermissions(Request $request)
{
    $request->validate([
        'model_type' => 'required|string',
        'model_id' => 'required|integer',
    ]);

    // Récupérer les permissions associées
    $permissions = DB::table('model_has_permissions')
        ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
        ->where('model_has_permissions.model_type', $request->model_type)
        ->where('model_has_permissions.model_id', $request->model_id)
        ->select('permissions.id', 'permissions.name')
        ->get();

    // Récupérer le modèle concerné dynamiquement
    $modelClass = $request->model_type; // Exemple : "App\Models\User"
    $model = null;

    if (class_exists($modelClass)) {
        $model = $modelClass::find($request->model_id); // Récupérer le modèle par ID
    }

    return response()->json([
        'permissions' => $permissions,
        'model' => $model, // Inclure les détails du modèle
    ]);
}

 // Mettre à jour une permission pour un modèle
 public function updateModelPermission(Request $request)
 {
     $request->validate([
         'old_permission_id' => 'required|exists:permissions,id',
         'new_permission_id' => 'required|exists:permissions,id',
         'model_type' => 'required|string',
         'model_id' => 'required|integer',
     ]);

     $updated = DB::table('model_has_permissions')
         ->where('permission_id', $request->old_permission_id)
         ->where('model_type', $request->model_type)
         ->where('model_id', $request->model_id)
         ->update([
             'permission_id' => $request->new_permission_id,
         ]);

     if ($updated) {
         return response()->json([
             'message' => 'Permission mise à jour avec succès.',
         ]);
     } else {
         return response()->json([
             'message' => 'Aucune mise à jour effectuée. Veuillez vérifier vos paramètres.',
         ], 400);
     }
 }

 // Révoquer une permission d'un modèle
 public function revokePermissionFromModel(Request $request)
 {
     $request->validate([
         'permission_id' => 'required|exists:permissions,id',
         'model_type' => 'required|string',
         'model_id' => 'required|integer',
     ]);

     $deleted = DB::table('model_has_permissions')
         ->where('permission_id', $request->permission_id)
         ->where('model_type', $request->model_type)
         ->where('model_id', $request->model_id)
         ->delete();

     if ($deleted) {
         return response()->json([
             'message' => 'Permission révoquée avec succès.',
         ]);
     } else {
         return response()->json([
             'message' => 'Aucune permission trouvée pour ce modèle.',
         ], 400);
     }
 }
}



