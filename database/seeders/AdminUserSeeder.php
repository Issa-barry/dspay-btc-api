<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Commande :
     * php artisan db:seed --class=AdminUserSeeder
     */
    public function run()
    {
        // 1) Vérifier / créer le rôle "Administrateur"
        $role = Role::firstOrCreate(['name' => 'Administrateur']);

        // 2) Vérifier si l'utilisateur admin existe déjà
        $admin = User::where('email', 'issabarry67@gmail.com')->first();

        if ($admin) {
            $this->command->info('Admin user already exists.');
            return;
        }

        // 3) Créer l'utilisateur admin (adresse intégrée dans users)
        $admin = User::create([
            'civilite'       => 'Mr',
            'prenom'         => 'Nom',
            'nom'            => 'Admin',
            'email'          => 'issabarry67@gmail.com',
            'phone'          => '0758855039',
            'date_naissance' => '1985-01-01',
            'password'       => Hash::make('Jeux@2019'),
            'role_id'        => $role->id,

            // ✅ Adresse intégrée dans users
            'pays'               => 'France',
            'country_code'               => 'FR',        // ISO2 : FR
            'dial_code'          => '+33',       // Indicatif international
            'adresse'            => '123 rue Admin',
            'complement_adresse' => 'Apt 45',
            'ville'              => 'Paris',
            'code_postal'        => '75000',
            'region'             => null,
            'quartier'           => null,
        ]);

        // 4) Assigner le rôle Spatie
        $admin->assignRole($role); // ou ->assignRole('Administrateur')

        // 5) Optionnel : envoyer la notification de vérification de l'email
        // $admin->sendEmailVerificationNotification();

        $this->command->info('Admin user has been created successfully!');
    }
}

// php artisan db:seed --class=AdminUserSeeder
