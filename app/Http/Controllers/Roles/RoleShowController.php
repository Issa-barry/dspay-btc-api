<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Exception;

class RoleShowController extends Controller
{
    use JsonResponseTrait;

    /**
     * Récupérer la liste de tous les rôles.
     */
    public function index()
    {
        try {
            $roles = Role::with('permissions')->get();

            return $this->responseJson(true, 'Liste des rôles récupérée avec succès.', $roles);
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la récupération des rôles.', $e->getMessage(), 500);
        }
    }

    /**
     * Récupérer un rôle par son ID.
     */
    public function getById($id)
    {
        try {
            $role = Role::with('permissions')->find($id);

            if (!$role) {
                return $this->responseJson(false, 'Rôle introuvable.', null, 404);
            }

            return $this->responseJson(true, 'Détails du rôle récupérés avec succès.', $role);
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la récupération du rôle.', $e->getMessage(), 500);
        }
    }

     /**
     * Récupérer un rôle par son nom.
     *
     * @param string $name
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByName($name)
    {
        try {
            // Recherche du rôle par son nom
            $role = Role::where('name', $name)->first();

            if (!$role) {
                return $this->responseJson(false, 'Rôle introuvable.', null, 404);
            }

            return $this->responseJson(true, 'Rôle trouvé avec succès.', $role);
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la récupération du rôle.', $e->getMessage(), 500);
        }
    }
    
}
