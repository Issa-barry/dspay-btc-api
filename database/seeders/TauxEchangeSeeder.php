<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TauxEchange;

class TauxEchangeSeeder extends Seeder
{
    public function run(): void
    {
        // EUR (id=1) -> GNF (id=2) avec taux entier 10700
        TauxEchange::updateOrCreate(
            ['devise_source_id' => 1, 'devise_cible_id' => 2],
            ['taux' => 10700]
        );
    }
}
