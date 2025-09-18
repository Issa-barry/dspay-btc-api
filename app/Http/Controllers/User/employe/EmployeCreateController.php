<?php

namespace App\Http\Controllers\User\Employe;

use App\Http\Controllers\Controller;
use App\Models\Adresse;
use App\Models\Role;
use App\Models\User;
use App\Notifications\CustomVerifyEmail;
use App\Traits\JsonResponseTrait;
use Exception;
use Hash;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EmployeCreateController extends Controller
{
    use JsonResponseTrait;

    public function store(Request $request)
    {
        try {
            // 1) Validation
            $validated = $request->validate([
                'civilite'                     => 'nullable|in:Mr,Mme,Mlle,Autre',
                'nom'                          => 'required|string|max:100',
                'prenom'                       => 'required|string|max:150',
                'email'                        => 'required|email|unique:users,email',
                'phone'                        => 'required|string|unique:users,phone',
                'date_naissance'               => 'nullable|date',
                'password'                     => 'required|string|min:8|confirmed',
                'role'                         => 'required|string|exists:roles,name',
                'adresse'                      => 'required|array',
                'adresse.pays'                 => 'required|string|max:255',
                'adresse.adresse'              => 'required|string|max:255',
                'adresse.complement_adresse'   => 'nullable|string|max:255',
                'adresse.ville'                => 'required|string|max:255',
                'adresse.quartier'             => 'required|string|max:255',
                'adresse.code_postal'          => 'required|string|max:20',
            ]);

            // 2) Transaction
            $user = DB::transaction(function () use ($validated) {

                // a) Vérifier que le rôle existe et récupérer son id
                $role = Role::where('name', $validated['role'])->first();
                if (!$role) {
                    throw new \Exception("Le rôle {$validated['role']} est introuvable.");
                }

                // b) Créer l'adresse
                $adresse = Adresse::create($validated['adresse']);

                // c) Créer l'utilisateur avec role_id obligatoire
                $user = User::create([
                    'civilite'       => $validated['civilite'] ?? 'Autre',
                    'nom'            => $validated['nom'],
                    'prenom'         => $validated['prenom'],
                    'email'          => $validated['email'],
                    'phone'          => $validated['phone'],
                    'date_naissance' => $validated['date_naissance'] ?? '9999-12-31',
                    'password'       => Hash::make($validated['password']),
                    'adresse_id'     => $adresse->id,
                    'role_id'        => $role->id, // ✅ obligatoire
                ]);

                // d) Spatie : assigner le rôle
                $user->assignRole($validated['role']);

                return $user;
            });

            // 3) Envoyer email de vérification (non bloquant)
            try {
                $user->notify(new CustomVerifyEmail());
            } catch (Exception $e) {
                Log::error("Email vérif non envoyé (user {$user->id}) : ".$e->getMessage());
                return $this->responseJson(true,
                    "Employé créé, mais l'email de vérification n'a pas pu être envoyé.",
                    $user->load(['adresse','roles']),
                    201
                );
            }

            return $this->responseJson(true,
                'Employé créé avec succès. Veuillez vérifier votre email.',
                $user->load(['adresse','roles']),
                201
            );

        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Erreur de validation.', $e->errors(), 422);

        } catch (QueryException $e) {
            Log::error('Erreur SQL création employé : ' . $e->getMessage());
            return $this->responseJson(false, $e->errorInfo[2] ?? 'Erreur de base de données.', null, 500);

        } catch (Exception $e) {
            Log::error('Erreur générale création employé : ' . $e->getMessage());
            return $this->responseJson(false, 'Une erreur inattendue est survenue.', $e->getMessage(), 500);
        }
    }
}
