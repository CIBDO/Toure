<?php

namespace Database\Seeders;

use App\Models\DomaineActivite;
use Illuminate\Database\Seeder;

class DomaineActiviteSeeder extends Seeder
{
    public function run(): void
    {
        $domaines = [
            ['code' => 'TRAVAUX',     'libelle' => 'Travaux de construction et génie civil'],
            ['code' => 'FOURNITURES', 'libelle' => 'Fournitures et équipements'],
            ['code' => 'SERVICES',    'libelle' => 'Prestations de services'],
            ['code' => 'CONSULTING',  'libelle' => 'Consulting et assistance technique'],
            ['code' => 'INFORMATIQUE','libelle' => 'Informatique et télécommunications'],
            ['code' => 'MEDICAL',     'libelle' => 'Équipements et fournitures médicales'],
            ['code' => 'TRANSPORT',   'libelle' => 'Transport et logistique'],
            ['code' => 'ENERGIE',     'libelle' => 'Énergie et électricité'],
            ['code' => 'AGRICULTURE', 'libelle' => 'Agriculture et agroalimentaire'],
            ['code' => 'SECURITE',    'libelle' => 'Sécurité et gardiennage'],
            ['code' => 'NETTOYAGE',   'libelle' => 'Nettoyage et entretien'],
            ['code' => 'FORMATION',   'libelle' => 'Formation et renforcement de capacités'],
            ['code' => 'AUDIT',       'libelle' => 'Audit et expertise comptable'],
            ['code' => 'JURIDIQUE',   'libelle' => 'Services juridiques'],
            ['code' => 'AUTRE',       'libelle' => 'Autres domaines'],
        ];

        foreach ($domaines as $domaine) {
            DomaineActivite::firstOrCreate(['code' => $domaine['code']], $domaine);
        }

        $this->command->info('Domaines d\'activité créés avec succès !');
    }
}
