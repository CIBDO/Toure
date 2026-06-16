<?php

namespace Database\Seeders;

use App\Models\Avis;
use App\Models\AvisItem;
use App\Models\CompteBudget;
use App\Models\Contrat;
use App\Models\ContratEtape;
use App\Models\Depouillement;
use App\Models\ExpressionBesoin;
use App\Models\Fournisseur;
use App\Models\Pv;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    private const ETAPES = ['elaboration', 'engagement', 'oem', 'mandat', 'paie'];

    public function run(): void
    {
        $admin = User::where('type_compte', 'SYSTEME')->first()
            ?? User::first();

        $fournisseurs = Fournisseur::all();
        $comptes = CompteBudget::where('exercice', '2026')->get();

        if ($fournisseurs->isEmpty() || $comptes->isEmpty()) {
            $this->command->warn('Seeders de référentiels manquants. Lancez BanqueSeeder, DomaineActiviteSeeder, CompteBudgetSeeder, FournisseurSeeder d\'abord.');
            return;
        }

        // Avis 1 - AO Ouvert Travaux
        $avis1 = Avis::firstOrCreate(
            ['reference' => 'CANAM/AO/2026/001'],
            [
                'objet' => 'Travaux de réhabilitation du siège de la CANAM',
                'mode_passation' => 'AO_OUVERT',
                'exercice' => '2026',
                'duree' => 30,
                'delai' => 60,
                'date_limite_depot' => now()->addDays(30),
                'date_ouverture_plis' => now()->addDays(32),
                'date_publication' => now()->subDays(10),
                'statut' => 'published',
                'created_by' => $admin?->id,
            ]
        );

        if ($avis1->wasRecentlyCreated) {
            $this->createAvisItem($avis1->id, 1, 'EB-001', ['quantite' => 1, 'unite' => 'forfait']);
            $this->createAvisItem($avis1->id, 2, 'EB-002', ['quantite' => 5, 'unite' => 'niveau']);
            $this->createAvisItem($avis1->id, 3, 'EB-003', ['quantite' => 1, 'unite' => 'forfait']);

            $avis1->fournisseurs()->sync($fournisseurs->take(3)->pluck('id')->toArray());
        }

        // Avis 2 - Consultation Fournitures
        $avis2 = Avis::firstOrCreate(
            ['reference' => 'CANAM/CONS/2026/002'],
            [
                'objet' => 'Acquisition de matériel informatique et bureautique',
                'mode_passation' => 'CONSULTATION',
                'exercice' => '2026',
                'duree' => 15,
                'delai' => 30,
                'date_limite_depot' => now()->subDays(5),
                'date_ouverture_plis' => now()->subDays(3),
                'date_publication' => now()->subDays(20),
                'statut' => 'closed',
                'created_by' => $admin?->id,
            ]
        );

        if ($avis2->wasRecentlyCreated) {
            $this->createAvisItem($avis2->id, 1, 'EB-004', ['quantite' => 20, 'unite' => 'unité']);
            $this->createAvisItem($avis2->id, 2, 'EB-005', ['quantite' => 5, 'unite' => 'unité']);
            $this->createAvisItem($avis2->id, 3, 'EB-006', ['quantite' => 1, 'unite' => 'unité']);

            $avis2->fournisseurs()->sync($fournisseurs->where('domaine_activite_id', $fournisseurs->firstWhere('code', 'F-003')?->domaine_activite_id)->pluck('id')->toArray());
        }

        // Dépouillement pour Avis 2
        $dep2 = Depouillement::firstOrCreate(
            ['reference' => 'DEP/CANAM/2026/002'],
            [
                'avis_id' => $avis2->id,
                'date_depouillement' => now()->subDays(2),
                'lieu' => 'Salle de réunion CANAM - Bamako',
                'resultats' => [
                    ['fournisseur' => 'TECH SOLUTIONS MALI', 'montant' => 185000000, 'note' => 87, 'rang' => 1],
                    ['fournisseur' => 'MALI ÉQUIPEMENTS SARL', 'montant' => 192000000, 'note' => 82, 'rang' => 2],
                ],
                'statut' => 'approved',
                'created_by' => $admin?->id,
            ]
        );

        // PV pour Avis 2
        $fournisseurTSG = Fournisseur::where('code', 'F-003')->first();
        $pv2 = Pv::firstOrCreate(
            ['reference' => 'PV/CANAM/2026/002'],
            [
                'depouillement_id' => $dep2->id,
                'avis_id' => $avis2->id,
                'fournisseur_attributaire_id' => $fournisseurTSG?->id,
                'date_pv' => now()->subDay(),
                'type_pv' => 'attribution',
                'montant_retenu' => 185000000,
                'contenu' => 'La commission d\'attribution déclare TECH SOLUTIONS MALI attributaire du marché pour l\'acquisition de matériel informatique et bureautique.',
                'statut' => 'approved',
                'created_by' => $admin?->id,
            ]
        );

        // Contrat lié au PV 2
        $compte = $comptes->firstWhere('code', 'CB-2026-004') ?? $comptes->first();
        $contrat2 = Contrat::firstOrCreate(
            ['reference' => 'CONT/CANAM/2026/002'],
            [
                'objet' => 'Acquisition de matériel informatique et bureautique - CANAM 2026',
                'pv_id' => $pv2->id,
                'avis_id' => $avis2->id,
                'fournisseur_id' => $fournisseurTSG?->id,
                'compte_budget_id' => $compte?->id,
                'agent_id' => $admin?->id,
                'montant_initial' => 185000000,
                'montant_actuel' => 185000000,
                'devise' => 'GNF',
                'date_signature' => now()->subDay(),
                'date_debut' => now(),
                'date_fin' => now()->addDays(30),
                'duree_execution' => 30,
                'mode_passation' => 'CONSULTATION',
                'exercice' => '2026',
                'statut' => 'approved',
                'created_by' => $admin?->id,
            ]
        );

        if ($contrat2->wasRecentlyCreated) {
            $etapesData = [
                ['type_etape' => 'elaboration', 'statut' => 'completed', 'date_effective' => now()->subDays(2)],
                ['type_etape' => 'engagement',  'statut' => 'in_progress', 'date_prevue' => now()->addDays(5)],
                ['type_etape' => 'oem',         'statut' => 'pending', 'date_prevue' => now()->addDays(15)],
                ['type_etape' => 'mandat',      'statut' => 'pending', 'date_prevue' => now()->addDays(25)],
                ['type_etape' => 'paie',        'statut' => 'pending', 'date_prevue' => now()->addDays(35)],
            ];
            foreach ($etapesData as $etape) {
                ContratEtape::create(array_merge($etape, ['contrat_id' => $contrat2->id]));
            }
        }

        // Avis 3 - Gré à gré Services
        $avis3 = Avis::firstOrCreate(
            ['reference' => 'CANAM/GRE/2026/003'],
            [
                'objet' => 'Prestation de services de gardiennage et sécurité',
                'mode_passation' => 'GRE_A_GRE',
                'exercice' => '2026',
                'duree' => 365,
                'statut' => 'published',
                'created_by' => $admin?->id,
            ]
        );

        // Contrat direct (sans PV pour gré à gré)
        $fournisseurSIG = Fournisseur::where('code', 'F-004')->first();
        $compte3 = $comptes->firstWhere('code', 'CB-2026-003') ?? $comptes->first();
        $contrat3 = Contrat::firstOrCreate(
            ['reference' => 'CONT/CANAM/2026/003'],
            [
                'objet' => 'Services de gardiennage et sécurité CANAM 2026',
                'avis_id' => $avis3->id,
                'fournisseur_id' => $fournisseurSIG?->id,
                'compte_budget_id' => $compte3?->id,
                'agent_id' => $admin?->id,
                'montant_initial' => 120000000,
                'montant_actuel' => 120000000,
                'devise' => 'GNF',
                'date_signature' => now()->subDays(10),
                'date_debut' => now()->subDays(10),
                'date_fin' => now()->addDays(355),
                'duree_execution' => 365,
                'mode_passation' => 'GRE_A_GRE',
                'exercice' => '2026',
                'statut' => 'approved',
                'created_by' => $admin?->id,
            ]
        );

        if ($contrat3->wasRecentlyCreated) {
            foreach (self::ETAPES as $etape) {
                ContratEtape::create([
                    'contrat_id' => $contrat3->id,
                    'type_etape' => $etape,
                    'statut' => 'pending',
                ]);
            }
        }

        $this->command->info('Données de démonstration créées avec succès !');
    }

    private function createAvisItem(int $avisId, int $ordre, string $expressionCode, array $attributes = []): void
    {
        $expression = ExpressionBesoin::where('code', $expressionCode)->first();

        AvisItem::create(array_merge([
            'avis_id'              => $avisId,
            'ordre'                => $ordre,
            'expression_besoin_id' => $expression?->id,
            'designation'          => $expression?->libelle ?? $expressionCode,
            'quantite'             => 1,
        ], $attributes));
    }
}
