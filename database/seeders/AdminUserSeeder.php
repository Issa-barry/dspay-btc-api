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

        // 3) Créer l'utilisateur admin
        $admin = User::create([
            'civilite' => 'Mr',
            'prenom' => 'Nom',
            'nom' => 'Admin',
            'email' => 'issabarry67@gmail.com',
            'phone' => '0123456789',
            'date_naissance' => '1985-01-01',
            'password' => Hash::make('Jeux@2019'),
            'role_id' => 1, // si tu gardes ce champ en DB
        ]);

        // 4) Créer l’adresse liée à l’utilisateur (remplit user_id automatiquement)
        // IMPORTANT : nécessite la relation User::adresse() = hasOne(Adresse::class)
        $admin->adresse()->create([
            'pays' => 'France',
            'adresse' => '123 rue Admin',
            'complement_adresse' => 'Apt 45',
            'ville' => 'Paris',
            'code_postal' => '75000',
        ]);

        // 5) Assigner le rôle Spatie
        $admin->assignRole($role); // ou ->assignRole('Administrateur')

        // 6) Optionnel : envoyer la notif de vérification email (à activer seulement si mail config OK)
        // $admin->sendEmailVerificationNotification();

        $this->command->info('Admin user has been created successfully!');
    }
}

//php artisan db:seed --class=AdminUserSeeder
