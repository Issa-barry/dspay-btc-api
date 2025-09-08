<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Adresse;
use App\Models\Role;
use App\Models\User;
use App\Notifications\CustomVerifyEmail;
use App\Traits\JsonResponseTrait; 
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Exception;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CreateUserController extends Controller
{
    use JsonResponseTrait; 

    public function store(Request $request)
    {
        try {
            // Validation des données
            $validated = $request->validate([  
                'civilite' => 'in:Mr,Mme,Mlle,Autre',
                'nom_complet' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'phone' => 'required|string|unique:users,phone',
                'date_naissance' => 'nullable|date',
                'password' => 'required|string|min:8|confirmed',
                'role' => 'required|string|exists:roles,name',
                'adresse' => 'required|array',
                'adresse.pays' => 'required|string|max:255',
                'adresse.adresse' => 'required|string|max:255',
                'adresse.complement_adresse' => 'nullable|string|max:255',
                'adresse.ville' => 'required|string|max:255',
                'adresse.quartier' => 'required|string|max:255',
                'adresse.code_postal' => 'required|string|max:20',
            ]);

            try {
                // Vérifier si le rôle existe
                $role = Role::where('name', $validated['role'])->first();
                if (!$role) {
                    return $this->responseJson(false, 'Rôle introuvable.', null, 404);
                }

                // Création de l'adresse
                $adresse = Adresse::create($validated['adresse']);

                // Création de l'utilisateur
                $user = User::create([
                    'civilite' => $validated['civilite'],
                    'nom_complet' => $validated['nom_complet'], 
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'date_naissance' => $validated['date_naissance'],
                    'password' => Hash::make($validated['password']),
                    'adresse_id' => $adresse->id,
                    'role_id' => $role->id,
                ]);

                // Attribution du rôle
                $user->assignRole($validated['role']);

                try {
                    // Envoi de l'email de vérification
                    // $user->sendEmailVerificationNotification();
                    $user->notify(new CustomVerifyEmail());
                } catch (Exception $e) {
                    Log::error('Erreur lors de l\'envoi de l\'email de vérification : ' . $e->getMessage());
                    return $this->responseJson(true, 'Utilisateur créé, mais l\'email de vérification n\'a pas pu être envoyé.', $user->load('adresse'), 201);
                }

                return $this->responseJson(true, 'Utilisateur créé avec succès. Veuillez vérifier votre email.', $user->load('adresse'), 201);

            } catch (QueryException $e) {
                Log::error('Erreur SQL lors de la création de l\'utilisateur : ' . $e->getMessage());
                return $this->responseJson(false, 'Erreur de base de données lors de la création de l\'utilisateur.', null, 500);
            }

        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Erreur de validation.', $e->errors(), 422);
        } catch (Exception $e) {
            Log::error('Erreur générale lors de la création de l\'utilisateur : ' . $e->getMessage());
            return $this->responseJson(false, 'Une erreur inattendue est survenue.', $e->getMessage(), 500);
        }
    }
}
