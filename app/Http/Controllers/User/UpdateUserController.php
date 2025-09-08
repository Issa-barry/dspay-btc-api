<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Traits\JsonResponseTrait; 
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;

class updateUserController extends Controller
{
    use JsonResponseTrait; 

    /**
     * Mettre à jour un utilisateur.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateById(Request $request, $id)
    {
        try {
            // Vérification de l'ID utilisateur
            if (!is_numeric($id)) {
                return $this->responseJson(false, 'ID utilisateur invalide.', null, 400);
            }

            // Vérifier si l'utilisateur existe
            $user = User::find($id);
            if (!$user) {
                return $this->responseJson(false, 'Utilisateur non trouvé.', null, 404);
            }

            // Validation des données
            $validator = Validator::make($request->all(), [
                'civilite' => 'nullable|in:Mr,Mme,Mlle,Autre',
                'nom_complet' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:users,email,' . $id,
                'phone' => [
                    'sometimes', 'required', 'string',
                    Rule::unique('users', 'phone')->ignore($id),
                ],
                'date_naissance' => 'nullable|date',
                'role' => [
                    'sometimes', 'required',
                    Rule::exists('roles', 'name'), // Vérifie que le rôle existe
                ],
                'adresse' => 'sometimes|array',
                'adresse.pays' => 'string|max:255',
                'adresse.adresse' => 'string|max:255',
                'adresse.complement_adresse' => 'nullable|string|max:255',
                'adresse.ville' => 'string|max:255',
                'adresse.quartier' => 'string|max:255',
                'adresse.code_postal' => 'string|max:20',
            ]);

            if ($validator->fails()) {
                return $this->responseJson(false, 'Erreur de validation.', $validator->errors(), 422);
            }

            $validated = $validator->validated();

            // Mise à jour de l'adresse si fournie
            if (isset($validated['adresse']) && $user->adresse) {
                $user->adresse->update($validated['adresse']);
            }

            // Mise à jour du rôle
            if (isset($validated['role'])) {
                $newRole = Role::where('name', $validated['role'])->first();
                if (!$newRole) {
                    return $this->responseJson(false, 'Le rôle spécifié n\'existe pas.', null, 400);
                }

                $user->role_id = $newRole->id;
                $user->save();
                $user->syncRoles([$validated['role']]);
            }

            // Mise à jour des autres champs
            $user->update(collect($validated)->except(['adresse', 'role'])->toArray());

            return $this->responseJson(true, 'Utilisateur mis à jour avec succès.', 
                array_merge($user->load(['adresse', 'roles'])->toArray(), [
                    'role' => $user->roles->pluck('name')->first(), //Récupération du rôle proprement
                    'role_id' => $user->role_id // Retourne aussi le `role_id`
                ])
            );
        } catch (\Exception $e) {
            return $this->responseJson(false, 'Une erreur est survenue lors de la mise à jour.', $e->getMessage(), 500);
        }
    }
}
