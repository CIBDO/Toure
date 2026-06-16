<?php

namespace Database\Seeders;

use App\Models\DomaineActivite;
use App\Models\ExpressionBesoin;
use Illuminate\Database\Seeder;

class ExpressionBesoinSeeder extends Seeder
{
    public function run(): void
    {
        $travaux = DomaineActivite::where('code', 'TRAVAUX')->first();
        $fournitures = DomaineActivite::where('code', 'FOURNITURES')->first();
        $informatique = DomaineActivite::where('code', 'INFORMATIQUE')->first();

        $expressions = [
            [
                'code' => 'EB-001',
                'libelle' => 'Réhabilitation façade principale',
                'description' => 'Travaux de réhabilitation de la façade du bâtiment principal',
                'unite_defaut' => 'forfait',
                'domaine_activite_id' => $travaux?->id,
            ],
            [
                'code' => 'EB-002',
                'libelle' => 'Réfection des bureaux (5 niveaux)',
                'description' => 'Réfection complète des espaces de bureaux',
                'unite_defaut' => 'niveau',
                'domaine_activite_id' => $travaux?->id,
            ],
            [
                'code' => 'EB-003',
                'libelle' => 'Installation électrique et climatisation',
                'unite_defaut' => 'forfait',
                'domaine_activite_id' => $travaux?->id,
            ],
            [
                'code' => 'EB-004',
                'libelle' => 'Ordinateurs portables HP EliteBook',
                'unite_defaut' => 'unité',
                'domaine_activite_id' => $informatique?->id,
            ],
            [
                'code' => 'EB-005',
                'libelle' => 'Imprimantes multifonctions',
                'unite_defaut' => 'unité',
                'domaine_activite_id' => $informatique?->id,
            ],
            [
                'code' => 'EB-006',
                'libelle' => 'Serveur NAS pour sauvegarde',
                'unite_defaut' => 'unité',
                'domaine_activite_id' => $informatique?->id,
            ],
            [
                'code' => 'EB-007',
                'libelle' => 'Fournitures de bureau',
                'unite_defaut' => 'lot',
                'domaine_activite_id' => $fournitures?->id,
            ],
            [
                'code' => 'EB-008',
                'libelle' => 'Mobilier de bureau',
                'unite_defaut' => 'unité',
                'domaine_activite_id' => $fournitures?->id,
            ],
        ];

        foreach ($expressions as $data) {
            ExpressionBesoin::firstOrCreate(
                ['code' => $data['code']],
                array_merge($data, ['actif' => true])
            );
        }

        $this->command->info('Expressions de besoin créées avec succès !');
    }
}
