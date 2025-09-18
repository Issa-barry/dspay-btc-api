<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $roles = [
            'Administrateur',          
            'Client', 
            'Agent',         
            'Responssable agence', 
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role]);
        }

        $this->command->info('Les rôles ont été créés avec succès !');
    }

    // 1) php artisan db:seed --class=RoleSeeder

}
