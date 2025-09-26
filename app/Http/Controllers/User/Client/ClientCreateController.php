<?php

namespace App\Http\Controllers\User\Client;

use App\Http\Controllers\Controller;
use App\Models\Adresse;
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
            // 1) Validation
            $validated = $request->validate([
                'civilite'                     => 'nullable|in:Mr,Mme,Mlle,Autre',
                'nom'                          => 'required|string|max:100',
                'prenom'                       => 'required|string|max:150',
                'email'                        => 'required|email|unique:users,email',
                'phone'                        => 'required|string', // unicité testée après normalisation E.164
                'date_naissance'               => 'nullable|date',
                'password'                     => 'required|string|min:8|confirmed',

                'adresse'                      => ['nullable','array'],
                'adresse.pays'                 => 'required|string|max:255',
                'adresse.code'                 => 'nullable|string|max:10',
                'adresse.adresse'              => 'nullable|string|max:255',
                'adresse.complement_adresse'   => 'nullable|string|max:255',
                'adresse.ville'                => 'nullable|string|max:255',
                'adresse.quartier'             => 'nullable|string|max:255',
                'adresse.code_postal'          => 'nullable|string|max:20',
                'adresse.region'               => 'nullable|string|max:255',
            ]);

            // 2) Normalisations
            $validated['nom']    = trim($validated['nom']);
            $validated['prenom'] = trim($validated['prenom']);
            $validated['email']  = strtolower(trim($validated['email']));

            // Pays pour le parsing du téléphone (ISO2), défaut FR
            $country = strtoupper($validated['adresse']['code'] ?? 'FR');

            // 3) Normalisation téléphone en E.164 (source of truth)
            try {
                $validated['phone'] = PhoneNumber::parse($validated['phone'], $country)
                    ->format(PhoneNumberFormat::E164); // ex: +33612345678
            } catch (PhoneNumberParseException $e) {
                return $this->responseJson(false, 'Numéro de téléphone invalide pour le pays sélectionné.', [
                    'phone' => ["Numéro invalide pour le pays {$country}."]
                ], 422);
            }

            // 3.b) Unicité après normalisation
            if (User::where('phone', $validated['phone'])->exists()) {
                return $this->responseJson(false, 'Le numéro de téléphone est déjà utilisé.', [
                    'phone' => ['Ce numéro est déjà pris.']
                ], 422);
            }

            // 4) Transaction: user -> adresse -> rôle
            [$user, $adresse] = DB::transaction(function () use ($validated) {
                // a) rôle
                $role = Role::where('name', 'Client')->first();
                if (!$role) {
                    throw new Exception("Le rôle Client est introuvable. Veuillez le créer d'abord.");
                }

                // b) user
                $user = User::create([
                    'civilite'       => $validated['civilite'] ?? 'Autre',
                    'nom'            => $validated['nom'],
                    'prenom'         => $validated['prenom'],
                    'email'          => $validated['email'],
                    'phone'          => $validated['phone'], // déjà E.164
                    'password'       => Hash::make($validated['password']),
                    'role_id'        => $role->id,
                ]);

                $user->assignRole('Client');

                // c) adresse (si absente, on met juste un pays par défaut)
                $addr = $validated['adresse'] ?? ['pays' => 'GUINÉE'];
                $pays = isset($addr['pays']) ? mb_strtoupper(trim($addr['pays'])) : 'GUINÉE';

                $adresse = Adresse::create([
                    'user_id'            => $user->id,        // << IMPORTANT
                    'pays'               => $pays,
                    'code'               => $addr['code'] ?? null,
                    'adresse'            => $addr['adresse'] ?? null,
                    'complement_adresse' => $addr['complement_adresse'] ?? null,
                    'ville'              => $addr['ville'] ?? null,
                    'quartier'           => $addr['quartier'] ?? null,
                    'code_postal'        => $addr['code_postal'] ?? null,
                    'region'             => $addr['region'] ?? null,
                ]);

                return [$user, $adresse];
            });

            // 5) Email de vérification (non bloquant)
            try {
                $user->notify(new CustomVerifyEmail());
            } catch (Exception $e) {
                Log::error("Email vérif non envoyé (user {$user->id}) : ".$e->getMessage());
                return $this->responseJson(
                    true,
                    "Client créé, mais l'email de vérification n'a pas pu être envoyé.",
                    $user->load(['adresse','roles']),
                    201
                );
            }

            // 6) OK
            return $this->responseJson(
                true,
                'Client créé avec succès. Veuillez vérifier votre email.',
                $user->load(['adresse','roles']),
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
