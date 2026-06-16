<?php

namespace Database\Seeders;

use App\Models\CompteBudget;
use Illuminate\Database\Seeder;

class CompteBudgetSeeder extends Seeder
{
    public function run(): void
    {
        $comptes = [
            [
                'code' => 'CB-2026-001',
                'libelle' => 'Budget Travaux Infrastructure',
                'exercice' => '2026',
                'montant_alloue' => 5000000000,
                'montant_engage' => 1200000000,
                'montant_disponible' => 3800000000,
            ],
            [
                'code' => 'CB-2026-002',
                'libelle' => 'Budget Fournitures et Équipements',
                'exercice' => '2026',
                'montant_alloue' => 2000000000,
                'montant_engage' => 450000000,
                'montant_disponible' => 1550000000,
            ],
            [
                'code' => 'CB-2026-003',
                'libelle' => 'Budget Prestations de Services',
                'exercice' => '2026',
                'montant_alloue' => 1500000000,
                'montant_engage' => 300000000,
                'montant_disponible' => 1200000000,
            ],
            [
                'code' => 'CB-2026-004',
                'libelle' => 'Budget Informatique et Télécoms',
                'exercice' => '2026',
                'montant_alloue' => 800000000,
                'montant_engage' => 120000000,
                'montant_disponible' => 680000000,
            ],
            [
                'code' => 'CB-2025-001',
                'libelle' => 'Budget Travaux Infrastructure 2025',
                'exercice' => '2025',
                'montant_alloue' => 4500000000,
                'montant_engage' => 4200000000,
                'montant_disponible' => 300000000,
            ],
        ];

        foreach ($comptes as $compte) {
            CompteBudget::firstOrCreate(['code' => $compte['code']], $compte);
        }

        $this->command->info('Comptes budget créés avec succès !');
    }
}
