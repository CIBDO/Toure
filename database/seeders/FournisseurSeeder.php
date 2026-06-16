<?php

namespace Database\Seeders;

use App\Models\Banque;
use App\Models\DomaineActivite;
use App\Models\Fournisseur;
use App\Models\FournisseurBanque;
use Illuminate\Database\Seeder;

class FournisseurSeeder extends Seeder
{
    public function run(): void
    {
        $bdm     = Banque::where('code', 'BDM')->first();
        $bnda    = Banque::where('code', 'BNDA')->first();
        $ecobank = Banque::where('code', 'ECOBANK')->first();

        $travaux     = DomaineActivite::where('code', 'TRAVAUX')->first();
        $fournitures = DomaineActivite::where('code', 'FOURNITURES')->first();
        $services    = DomaineActivite::where('code', 'SERVICES')->first();
        $informatique= DomaineActivite::where('code', 'INFORMATIQUE')->first();

        $fournisseurs = [
            [
                'code' => 'F-001',
                'raison_sociale' => 'SOGEA-SATOM Mali',
                'sigle' => 'SOGEA',
                'nif' => '123456789',
                'rc' => 'RC/BAM/2010/B/001234',
                'telephone' => '+223 20 22 00 01',
                'email' => 'contact@sogea-mali.ml',
                'adresse' => 'Avenue de la Nation, Bamako',
                'ville' => 'Bamako',
                'representant' => 'Jean-Pierre MARTIN',
                'fonction_representant' => 'Directeur Général',
                'domaine_activite_id' => $travaux?->id,
                'modes_passation' => ['AO_OUVERT', 'AO_RESTREINT'],
                'duree_min' => 15,
                'duree_max' => 90,
                'statut' => 'actif',
                'banques' => [
                    ['banque_id' => $bdm?->id, 'numero_compte' => 'ML-0001-0000-0001234-56', 'principal' => true],
                ],
            ],
            [
                'code' => 'F-002',
                'raison_sociale' => 'MALI ÉQUIPEMENTS SARL',
                'sigle' => 'ME-SARL',
                'nif' => '987654321',
                'rc' => 'RC/BAM/2015/B/005678',
                'telephone' => '+223 20 22 00 02',
                'email' => 'info@me-sarl.ml',
                'adresse' => 'Quartier du Fleuve, Bamako',
                'ville' => 'Bamako',
                'representant' => 'Mamadou DIALLO',
                'fonction_representant' => 'Gérant',
                'domaine_activite_id' => $fournitures?->id,
                'modes_passation' => ['CONSULTATION', 'AO_OUVERT'],
                'duree_min' => 10,
                'duree_max' => 30,
                'statut' => 'actif',
                'banques' => [
                    ['banque_id' => $bnda?->id, 'numero_compte' => 'ML-0002-0000-0005678-90', 'principal' => true],
                ],
            ],
            [
                'code' => 'F-003',
                'raison_sociale' => 'TECH SOLUTIONS MALI',
                'sigle' => 'TSM',
                'nif' => '456789123',
                'rc' => 'RC/BAM/2018/B/009012',
                'telephone' => '+223 20 22 00 03',
                'email' => 'contact@tsm.ml',
                'adresse' => 'ACI 2000, Bamako',
                'ville' => 'Bamako',
                'representant' => 'Fatoumata CAMARA',
                'fonction_representant' => 'Directrice',
                'domaine_activite_id' => $informatique?->id,
                'modes_passation' => ['CONSULTATION', 'GRE_A_GRE'],
                'duree_min' => 10,
                'duree_max' => 45,
                'statut' => 'actif',
                'banques' => [
                    ['banque_id' => $ecobank?->id, 'numero_compte' => 'ML-0003-0000-0009012-34', 'principal' => true],
                ],
            ],
            [
                'code' => 'F-004',
                'raison_sociale' => 'SERVICES INTÉGRÉS MALI',
                'sigle' => 'SIM',
                'nif' => '321654987',
                'rc' => 'RC/BAM/2012/B/003456',
                'telephone' => '+223 20 22 00 04',
                'email' => 'sim@services-mali.ml',
                'adresse' => 'Hamdallaye, Bamako',
                'ville' => 'Bamako',
                'representant' => 'Ibrahima KOUYATÉ',
                'fonction_representant' => 'PDG',
                'domaine_activite_id' => $services?->id,
                'modes_passation' => ['CONSULTATION', 'ENTENTE_DIRECTE'],
                'duree_min' => 5,
                'duree_max' => 60,
                'statut' => 'actif',
                'banques' => [
                    ['banque_id' => $bdm?->id, 'numero_compte' => 'ML-0004-0000-0003456-78', 'principal' => true],
                ],
            ],
            [
                'code' => 'F-005',
                'raison_sociale' => 'BÂTIMENT ET TRAVAUX PUBLICS ML',
                'sigle' => 'BTP-ML',
                'nif' => '654321789',
                'rc' => 'RC/BAM/2008/B/000789',
                'telephone' => '+223 20 22 00 05',
                'email' => 'btp@btpml.ml',
                'adresse' => 'Kalaban-Coura, Bamako',
                'ville' => 'Bamako',
                'representant' => 'Oumar BARRY',
                'fonction_representant' => 'Directeur Technique',
                'domaine_activite_id' => $travaux?->id,
                'modes_passation' => ['AO_OUVERT', 'AO_RESTREINT', 'GRE_A_GRE'],
                'duree_min' => 20,
                'duree_max' => 120,
                'statut' => 'actif',
                'banques' => [
                    ['banque_id' => $bnda?->id, 'numero_compte' => 'ML-0005-0000-0000789-12', 'principal' => true],
                ],
            ],
        ];

        foreach ($fournisseurs as $data) {
            $banquesData = $data['banques'] ?? [];
            $modes = $data['modes_passation'] ?? null;
            unset($data['banques']);

            $fournisseur = Fournisseur::firstOrCreate(['code' => $data['code']], $data);

            if ($modes !== null) {
                $fournisseur->update([
                    'modes_passation' => $modes,
                    'duree_min' => $data['duree_min'] ?? null,
                    'duree_max' => $data['duree_max'] ?? null,
                ]);
            }

            if ($fournisseur->wasRecentlyCreated) {
                foreach ($banquesData as $banque) {
                    if ($banque['banque_id']) {
                        FournisseurBanque::create(array_merge($banque, ['fournisseur_id' => $fournisseur->id]));
                    }
                }
            }
        }

        $this->command->info('Fournisseurs créés avec succès !');
    }
}
