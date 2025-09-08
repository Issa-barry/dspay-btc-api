<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class GeneratePermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:generate {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate permissions for a given model';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $model = $this->argument('model');
        $actions = ['afficher', 'créer', 'modifier', 'supprimer'];

        foreach ($actions as $action) {
            Permission::firstOrCreate(
                [
                    'name' => "$action $model",
                    'model_type' => ucfirst(strtolower($model)),
                ],
                [
                    'guard_name' => 'web',  
                ]
            );
        }

        $this->info("Permissions for $model created successfully!");
    }
    
}
