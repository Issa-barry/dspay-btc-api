<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\JsonResponseTrait; 
use Exception;
use Illuminate\Http\Request;

class DeleteUserController extends Controller
{
    use JsonResponseTrait; 

    /**
     * Supprimer un utilisateur par ID.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delateById($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->responseJson(false, 'ID utilisateur invalide.', null, 400);
            }

            $user = User::find($id);

            if (!$user) {
                return $this->responseJson(false, 'Utilisateur non trouvé.', null, 404);
            }

            $user->delete();

            return $this->responseJson(true, 'Utilisateur supprimé avec succès.', null, 200);
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la suppression de l\'utilisateur.', $e->getMessage(), 500);
        }
    }
}
