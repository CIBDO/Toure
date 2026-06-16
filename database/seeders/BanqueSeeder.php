<?php

namespace Database\Seeders;

use App\Models\Banque;
use Illuminate\Database\Seeder;

class BanqueSeeder extends Seeder
{
    public function run(): void
    {
        $banques = [
            ['code' => 'BCRM',    'libelle' => 'Banque Centrale du Mali',                                        'sigle' => 'BCRM'],
            ['code' => 'BDM',     'libelle' => 'Banque de Développement du Mali',                               'sigle' => 'BDM'],
            ['code' => 'BNDA',    'libelle' => 'Banque Nationale de Développement Agricole',                    'sigle' => 'BNDA'],
            ['code' => 'ECOBANK', 'libelle' => 'Ecobank Mali',                                                  'sigle' => 'ECOBANK'],
            ['code' => 'UBA',     'libelle' => 'United Bank for Africa Mali',                                   'sigle' => 'UBA'],
            ['code' => 'ORABANK', 'libelle' => 'Orabank Mali',                                                  'sigle' => 'ORABANK'],
            ['code' => 'BIM',     'libelle' => 'Banque Internationale pour le Mali',                            'sigle' => 'BIM'],
            ['code' => 'CORIS',   'libelle' => 'Coris Bank International Mali',                                 'sigle' => 'CORIS'],
        ];

        foreach ($banques as $banque) {
            Banque::firstOrCreate(['code' => $banque['code']], $banque);
        }

        $this->command->info('Banques créées avec succès !');
    }
}
