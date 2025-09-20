<?php

namespace App\Http\Controllers\User\Client;

use App\Http\Controllers\Controller;
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

class ClientCreateController extends Controller
{
    use JsonResponseTrait;

    public function store(Request $request)
    {
        try {
            // 1) Validation (nom & prenom requis désormais)
            $validated = $request->validate([
                'nom'       => 'required|string|max:100',
                'prenom'    => 'required|string|max:100',
                'email'     => 'required|email|unique:users,email',
                'phone'     => 'required|string|unique:users,phone',
                'password'  => 'required|string|min:8|confirmed',
            ]);

            // Normalisation simple
            $validated['nom']    = trim($validated['nom']);
            $validated['prenom'] = trim($validated['prenom']);

            // 2) Transaction: user + rôle
            $user = DB::transaction(function () use ($validated) {
                // a) rôle "Client" obligatoire
                $role = Role::where('name', 'Client')->first();
                if (!$role) {
                    throw new Exception("Le rôle 'Client' est introuvable. Crée-le d'abord.");
                }

                // b) création user (avec nom & prenom)
                $user = User::create([
                    'nom'      => $validated['nom'],
                    'prenom'   => $validated['prenom'],
                    'email'    => $validated['email'],
                    'phone'    => $validated['phone'],
                    'password' => Hash::make($validated['password']),
                    'role_id'  => $role->id,
                ]);

                // c) Spatie
                $user->assignRole('Client');

                return $user;
            });

            // 3) Email de vérification (non bloquant)
            try {
                $user->notify(new CustomVerifyEmail());
            } catch (Exception $e) {
                Log::error("Email vérif non envoyé (client {$user->id}) : ".$e->getMessage());
                return $this->responseJson(
                    true,
                    "Compte client créé, mais l'email de vérification n'a pas pu être envoyé.",
                    $user->only(['id','nom','prenom','email','phone']),
                    201
                );
            }

            return $this->responseJson(
                true,
                'Compte client créé avec succès. Veuillez vérifier votre email.',
                $user->only(['id','nom','prenom','email','phone']),
                201
            );

        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Erreur de validation.', $e->errors(), 422);

        } catch (QueryException $e) {
            Log::error('Erreur SQL création client : ' . $e->getMessage());
            return $this->responseJson(false, $e->errorInfo[2] ?? 'Erreur SQL', null, 500);

        } catch (Exception $e) {
            Log::error('Erreur générale création client : ' . $e->getMessage());
            return $this->responseJson(false, 'Une erreur inattendue est survenue.', $e->getMessage(), 500);
        }
    }
}
