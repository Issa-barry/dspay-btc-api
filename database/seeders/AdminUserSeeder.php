<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Adresse;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Vérifier si le rôle 'admin' existe, sinon le créer
        $role = Role::firstOrCreate(['name' => 'Administrateur']);

        // Vérifier si l'utilisateur admin existe déjà
        $admin = User::where('email', 'admin@example.com')->first();

        if (!$admin) {
            // Créer l'adresse de l'utilisateur admin
            $adresse = Adresse::create([
                'pays' => 'France',
                'adresse' => '123 rue Admin ',
                'complement_adresse' => 'Apt 45',
                'ville' => 'Paris',
                'code_postal' => '75000'
            ]);

            // Créer l'utilisateur admin
            $admin = User::create([
                'civilite' => 'Mr',
                'nom_complet' => 'Nom_Admin',
                'email' => 'wotapif@gmail.com',
                'phone' => '0123456789',
                'date_naissance' => '1985-01-01',
                'password' => Hash::make('P@ssword1'), // N'oubliez pas de sécuriser le mot de passe
                'adresse_id' => $adresse->id,
                'role_id' => 1,
            ]);

            // Assigner le rôle admin à l'utilisateur
            $admin->assignRole('Administrateur');
            $admin->sendEmailVerificationNotification();
            // Optionnel : Assigner des permissions spécifiques à l'administrateur
            // Exemple: $admin->givePermissionTo('create posts');

            $this->command->info('Admin user has been created successfully!');
        } else {
            $this->command->info('Admin user already exists.');
        }
    }

    /**
     * Coommande : 
     * php artisan db:seed --class=AdminUserSeeder
     */
}
