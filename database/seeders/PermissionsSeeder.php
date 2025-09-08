<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Exemple de modèles pour lesquels créer des permissions
        $models = ['Transfert', 'Contact', 'Agence','Agents','Factures', 'Taux', 'Frais', 'Devises', 'Users', 'Roles', 'Permissions', 
                   'Dashboard-RH', 'Dashboard-CA']; 

        // Liste des actions possibles
        $actions = ['afficher', 'créer', 'modifier', 'supprimer'];

        // Vérifier si le rôle Admin existe, sinon le créer
        $adminRole = Role::firstOrCreate(['name' => 'Administrateur']);

        foreach ($models as $model) {
            foreach ($actions as $action) {
                // Créer ou récupérer la permission pour l'action et le modèle spécifiés
                $permission = Permission::firstOrCreate([
                    'name' => "$action $model",
                    'model_type' => ucfirst(strtolower($model)), // Mettre la première lettre du modèle en majuscule
                ]);

                // Assigner la permission au rôle Admin
                if (!$adminRole->hasPermissionTo($permission)) {
                    $adminRole->givePermissionTo($permission);
                }
            }
        }

        $this->command->info('Permissions have been seeded and assigned to the Admin role successfully!');
    }

    /**
     * Dans le terminal, exécutez la commande suivante pour lancer le seeder :
     *   php artisan db:seed --class=PermissionsSeeder
     */
}
