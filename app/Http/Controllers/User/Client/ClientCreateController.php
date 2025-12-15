<?php

namespace App\Http\Controllers\User\Client;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Notifications\CustomVerifyEmail;
use App\Traits\JsonResponseTrait;
use Brick\PhoneNumber\PhoneNumber;
use Brick\PhoneNumber\PhoneNumberFormat;
use Brick\PhoneNumber\PhoneNumberParseException;
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
            // 1) Validation - ✅ Accepter "code" OU "country_code"
            $validated = $request->validate([
                'civilite'        => 'nullable|in:Mr,Mme,Mlle,Autre',
                'nom'             => 'required|string|max:100',
                'prenom'          => 'required|string|max:150',
                'email'           => 'required|email|unique:users,email',
                'phone'           => 'required|string',
                'date_naissance'  => 'nullable|date',
                'password'        => 'required|string|min:8|confirmed',

                // Adresse / Pays - ✅ Accepter les deux
                'pays'               => 'required|string|max:255',
                'code'               => 'nullable|string|max:10',        // Pour compatibilité
                'country_code'       => 'nullable|string|max:2',         // Nouveau standard
                'dial_code'          => 'nullable|string|max:5',

                'adresse'            => 'nullable|string|max:255',
                'complement_adresse' => 'nullable|string|max:255',
                'ville'              => 'nullable|string|max:255',
                'quartier'           => 'nullable|string|max:255',
                'code_postal'        => 'nullable|string|max:20',
                'region'             => 'nullable|string|max:255',
            ]);

            // 2) Normalisations
            $validated['nom']    = trim($validated['nom']);
            $validated['prenom'] = trim($validated['prenom']);
            $validated['email']  = strtolower(trim($validated['email']));
            $validated['pays']   = mb_strtoupper(trim($validated['pays']));

            // ✅ Unifier country_code (prendre country_code OU code)
            $countryCode = strtoupper($validated['country_code'] ?? $validated['code'] ?? 'FR');

            // 3) Normalisation téléphone en E.164
            try {
                $validated['phone'] = PhoneNumber::parse($validated['phone'], $countryCode)
                    ->format(PhoneNumberFormat::E164);
            } catch (PhoneNumberParseException $e) {
                return $this->responseJson(false, 'Numéro de téléphone invalide pour le pays sélectionné.', [
                    'phone' => ["Numéro invalide pour le pays {$countryCode}."]
                ], 422);
            }

            // 3.a) Déduire dial_code si absent
            $dialCode = $validated['dial_code'] ?? null;
            if (!$dialCode) {
                try {
                    $parsed = PhoneNumber::parse($validated['phone']);
                    $dialCode = '+' . $parsed->getCountryCode();
                } catch (PhoneNumberParseException $e) {
                    $dialCode = null;
                }
            }

            // 3.b) Unicité après normalisation
            if (User::where('phone', $validated['phone'])->exists()) {
                return $this->responseJson(false, 'Le numéro de téléphone est déjà utilisé.', [
                    'phone' => ['Ce numéro est déjà pris.']
                ], 422);
            }

            // 4) Transaction : user -> rôle
            $user = DB::transaction(function () use ($validated, $dialCode, $countryCode) {
                $role = Role::where('name', 'Client')->first();
                if (!$role) {
                    throw new Exception("Le rôle Client est introuvable. Veuillez le créer d'abord.");
                }

                $user = User::create([
                    'civilite'       => $validated['civilite'] ?? 'Autre',
                    'nom'            => $validated['nom'],
                    'prenom'         => $validated['prenom'],
                    'email'          => $validated['email'],
                    'phone'          => $validated['phone'],
                    'date_naissance' => $validated['date_naissance'] ?? '9999-12-31',
                    'password'       => Hash::make($validated['password']),
                    'role_id'        => $role->id,

                    // ✅ Utiliser country_code (colonne unifiée)
                    'pays'               => $validated['pays'],
                    'country_code'       => $countryCode,    // ✅ Colonne standard
                    'dial_code'          => $dialCode,
                    'adresse'            => $validated['adresse'] ?? null,
                    'complement_adresse' => $validated['complement_adresse'] ?? null,
                    'ville'              => $validated['ville'] ?? null,
                    'quartier'           => $validated['quartier'] ?? null,
                    'code_postal'        => $validated['code_postal'] ?? null,
                    'region'             => $validated['region'] ?? null,
                ]);

                $user->assignRole('Client');

                return $user;
            });

            // 5) Email de vérification (non bloquant)
            try {
                $user->notify(new CustomVerifyEmail());
            } catch (Exception $e) {
                Log::error("Email vérif non envoyé (user {$user->id}) : " . $e->getMessage());

                return $this->responseJson(
                    true,
                    "Compte créé, mais l'email de vérification n'a pas pu être envoyé.",
                    $user->load('roles'),
                    201
                );
            }

            // 6) OK
            return $this->responseJson(
                true,
                'Compte créé avec succès. Veuillez vérifier votre email.',
                $user->load('roles'),
                201
            );

        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Erreur de validation.', $e->errors(), 422);

        } catch (QueryException $e) {
            Log::error('Erreur SQL création client : ' . $e->getMessage());
            return $this->responseJson(false, $e->errorInfo[2] ?? 'Erreur de base de données.', null, 500);

        } catch (Exception $e) {
            Log::error('Erreur générale création client : ' . $e->getMessage());
            return $this->responseJson(false, 'Une erreur inattendue est survenue.', $e->getMessage(), 500);
        }
    }
}