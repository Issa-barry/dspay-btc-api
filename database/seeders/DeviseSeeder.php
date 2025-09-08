<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Devise;

class DeviseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    { 
        $devises = [
            ['nom' => 'Euro', 'tag' => '€'],
            ['nom' => 'Franc-Guinéen', 'tag' => 'GNF'],
            ['nom' => 'Dollar US', 'tag' => '$'],
        ];
 
        foreach ($devises as $devise) {
            Devise::create($devise);
        }

        $this->command->info('Devises insérées avec succès!');
    }
    
    /**
     * php artisan db:seed --class=DeviseSeeder
     */
}
