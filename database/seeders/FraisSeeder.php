<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Frais;

class FraisSeeder extends Seeder
{
    public function run(): void
    {
        // Barème par défaut : 5% applicable à partir de 0€ (sans plafond)
        $rows = [
            [
                'nom'         => 'standard',
                'type'        => 'pourcentage', // 'fixe' ou 'pourcentage'
                'valeur'      => 5,             // 5 => 5%
                'montant_min' => 0,
                'montant_max' => 100,          // null = sans limite supérieure
            ],
        ];

        foreach ($rows as $data) {
            Frais::updateOrCreate(
                ['nom' => $data['nom'], 'type' => $data['type']],
                $data
            );
        }
    }

    // php artisan db:seed --class=FraisSeeder
}
