<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

/**
 * Seeder pour les permissions du système IAM CANAM
 *
 * Structure des codes : GROUPE_ACTION
 * Groupes : DASHBOARD, AVIS, DEPOUILLEMENTS, PVS, CONTRATS,
 *           FOURNISSEURS, REFERENTIELS, GED, FINANCES,
 *           RAPPORTS, USERS, ROLES, PERMISSIONS, AUDIT, SYSTEM
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            // ─── TABLEAU DE BORD ─────────────────────────────────────────────
            ['code' => 'DASHBOARD_READ',   'libelle' => 'Consulter le tableau de bord'],

            // ─── PASSATION — AVIS DE PASSATION ───────────────────────────────
            ['code' => 'AVIS_READ',        'libelle' => 'Consulter les avis de passation'],
            ['code' => 'AVIS_CREATE',      'libelle' => 'Créer un avis de passation'],
            ['code' => 'AVIS_EDIT',        'libelle' => 'Modifier un avis de passation'],
            ['code' => 'AVIS_DELETE',      'libelle' => 'Supprimer un avis de passation'],
            ['code' => 'AVIS_SUBMIT',      'libelle' => 'Soumettre un avis pour validation'],
            ['code' => 'AVIS_APPROVE',     'libelle' => 'Approuver un avis de passation'],
            ['code' => 'AVIS_REJECT',      'libelle' => 'Rejeter un avis de passation'],
            ['code' => 'AVIS_PUBLISH',     'libelle' => 'Publier un avis de passation'],
            ['code' => 'AVIS_CLOSE',       'libelle' => 'Clôturer un avis de passation'],

            // ─── PASSATION — DÉPOUILLEMENTS ───────────────────────────────────
            ['code' => 'DEPOUILLEMENTS_READ',    'libelle' => 'Consulter les dépouillements'],
            ['code' => 'DEPOUILLEMENTS_CREATE',  'libelle' => 'Créer un dépouillement'],
            ['code' => 'DEPOUILLEMENTS_EDIT',    'libelle' => 'Modifier un dépouillement'],
            ['code' => 'DEPOUILLEMENTS_DELETE',  'libelle' => 'Supprimer un dépouillement'],
            ['code' => 'DEPOUILLEMENTS_SUBMIT',  'libelle' => 'Soumettre un dépouillement pour validation'],
            ['code' => 'DEPOUILLEMENTS_APPROVE', 'libelle' => 'Approuver un dépouillement'],
            ['code' => 'DEPOUILLEMENTS_REJECT',  'libelle' => 'Rejeter un dépouillement'],

            // ─── PASSATION — PROCÈS-VERBAUX ───────────────────────────────────
            ['code' => 'PVS_READ',         'libelle' => 'Consulter les procès-verbaux'],
            ['code' => 'PVS_CREATE',       'libelle' => 'Créer un procès-verbal'],
            ['code' => 'PVS_EDIT',         'libelle' => 'Modifier un procès-verbal'],
            ['code' => 'PVS_DELETE',       'libelle' => 'Supprimer un procès-verbal'],
            ['code' => 'PVS_SUBMIT',       'libelle' => 'Soumettre un PV pour validation'],
            ['code' => 'PVS_APPROVE',      'libelle' => 'Approuver un procès-verbal'],
            ['code' => 'PVS_REJECT',       'libelle' => 'Rejeter un procès-verbal'],
            ['code' => 'PVS_GENERATE_PDF', 'libelle' => 'Générer le PDF d\'un procès-verbal'],
            ['code' => 'PVS_UPLOAD_SIGNE', 'libelle' => 'Téléverser le PV signé'],

            // ─── CONTRATS ─────────────────────────────────────────────────────
            ['code' => 'CONTRATS_READ',    'libelle' => 'Consulter les contrats'],
            ['code' => 'CONTRATS_CREATE',  'libelle' => 'Créer un contrat'],
            ['code' => 'CONTRATS_EDIT',    'libelle' => 'Modifier un contrat'],
            ['code' => 'CONTRATS_DELETE',  'libelle' => 'Supprimer un contrat'],
            ['code' => 'CONTRATS_SUBMIT',  'libelle' => 'Soumettre un contrat pour validation'],
            ['code' => 'CONTRATS_APPROVE', 'libelle' => 'Approuver un contrat'],
            ['code' => 'CONTRATS_REJECT',  'libelle' => 'Rejeter un contrat'],
            ['code' => 'CONTRATS_ARCHIVE', 'libelle' => 'Archiver un contrat'],
            ['code' => 'CONTRATS_ETAPES',  'libelle' => 'Gérer les étapes d\'exécution d\'un contrat'],

            // ─── AVENANTS (Phase 2) ─────────────────────────────────────────────
            ['code' => 'AVENANTS_READ',    'libelle' => 'Consulter les avenants'],
            ['code' => 'AVENANTS_CREATE',  'libelle' => 'Créer un avenant'],
            ['code' => 'AVENANTS_EDIT',    'libelle' => 'Modifier un avenant'],
            ['code' => 'AVENANTS_DELETE',  'libelle' => 'Supprimer un avenant'],
            ['code' => 'AVENANTS_SUBMIT',  'libelle' => 'Soumettre un avenant pour validation'],
            ['code' => 'AVENANTS_APPROVE', 'libelle' => 'Approuver / rejeter un avenant'],

            // ─── ORDRES DE SERVICE (Phase 2) ─────────────────────────────────────
            ['code' => 'OS_READ',    'libelle' => 'Consulter les ordres de service'],
            ['code' => 'OS_CREATE',  'libelle' => 'Créer un ordre de service'],
            ['code' => 'OS_EDIT',    'libelle' => 'Modifier un ordre de service'],
            ['code' => 'OS_DELETE',  'libelle' => 'Supprimer un ordre de service'],
            ['code' => 'OS_SUBMIT',  'libelle' => 'Soumettre un ordre de service pour validation'],
            ['code' => 'OS_APPROVE', 'libelle' => 'Approuver / rejeter un ordre de service'],
            ['code' => 'OS_EXECUTE', 'libelle' => 'Marquer un ordre de service comme exécuté'],

            // ─── RÉCEPTIONS (Phase 2) — PV de réception ───────────────────────────
            ['code' => 'RECEPTION_READ',    'libelle' => 'Consulter les réceptions'],
            ['code' => 'RECEPTION_CREATE',  'libelle' => 'Créer une réception'],
            ['code' => 'RECEPTION_EDIT',    'libelle' => 'Modifier une réception'],
            ['code' => 'RECEPTION_DELETE',  'libelle' => 'Supprimer une réception'],
            ['code' => 'RECEPTION_SUBMIT',  'libelle' => 'Soumettre une réception pour validation'],
            ['code' => 'RECEPTION_APPROVE', 'libelle' => 'Approuver / rejeter une réception'],
            ['code' => 'RECEPTION_OVERRIDE_DEFINITIVE', 'libelle' => 'Approuver réception définitive sans réception provisoire'],

            // ─── FOURNISSEURS ─────────────────────────────────────────────────
            ['code' => 'FOURNISSEURS_READ',   'libelle' => 'Consulter les fournisseurs'],
            ['code' => 'FOURNISSEURS_CREATE', 'libelle' => 'Créer un fournisseur'],
            ['code' => 'FOURNISSEURS_EDIT',   'libelle' => 'Modifier un fournisseur'],
            ['code' => 'FOURNISSEURS_DELETE', 'libelle' => 'Supprimer un fournisseur'],

            // ─── RÉFÉRENTIELS ─────────────────────────────────────────────────
            ['code' => 'REFERENTIELS_READ',   'libelle' => 'Consulter les référentiels (banques, domaines, comptes)'],
            ['code' => 'REFERENTIELS_CREATE', 'libelle' => 'Créer des entrées dans les référentiels'],
            ['code' => 'REFERENTIELS_EDIT',   'libelle' => 'Modifier les référentiels'],
            ['code' => 'REFERENTIELS_DELETE', 'libelle' => 'Supprimer des entrées de référentiels'],

            // ─── GED — GESTION ÉLECTRONIQUE DES DOCUMENTS ────────────────────
            ['code' => 'GED_READ',    'libelle' => 'Consulter les documents (GED)'],
            ['code' => 'GED_EDIT',    'libelle' => 'Modifier les métadonnées des documents (GED)'],
            ['code' => 'GED_UPLOAD',  'libelle' => 'Téléverser des documents'],
            ['code' => 'GED_DOWNLOAD','libelle' => 'Télécharger des documents'],
            ['code' => 'GED_DELETE',  'libelle' => 'Supprimer des documents'],

            // ─── FINANCES ─────────────────────────────────────────────────────
            ['code' => 'FINANCES_READ',   'libelle' => 'Consulter les données financières'],
            ['code' => 'FINANCES_CREATE', 'libelle' => 'Créer des engagements / paiements'],
            ['code' => 'FINANCES_EDIT',   'libelle' => 'Modifier les engagements / paiements'],
            ['code' => 'FINANCES_APPROVE','libelle' => 'Approuver les opérations financières'],

            // ─── ENGAGEMENTS ──────────────────────────────────────────────────
            ['code' => 'ENGAGEMENTS_READ',    'libelle' => 'Consulter les engagements budgétaires'],
            ['code' => 'ENGAGEMENTS_CREATE',  'libelle' => 'Créer un engagement budgétaire'],
            ['code' => 'ENGAGEMENTS_EDIT',    'libelle' => 'Modifier un engagement budgétaire'],
            ['code' => 'ENGAGEMENTS_DELETE',  'libelle' => 'Supprimer un engagement budgétaire'],
            ['code' => 'ENGAGEMENTS_SUBMIT',  'libelle' => 'Soumettre un engagement pour validation'],
            ['code' => 'ENGAGEMENTS_APPROVE', 'libelle' => 'Approuver / rejeter un engagement'],

            // ─── PAIEMENTS ────────────────────────────────────────────────────
            ['code' => 'PAIEMENTS_READ',    'libelle' => 'Consulter les paiements'],
            ['code' => 'PAIEMENTS_CREATE',  'libelle' => 'Créer un paiement'],
            ['code' => 'PAIEMENTS_EDIT',    'libelle' => 'Modifier un paiement'],
            ['code' => 'PAIEMENTS_DELETE',  'libelle' => 'Supprimer un paiement'],
            ['code' => 'PAIEMENTS_SUBMIT',  'libelle' => 'Soumettre un paiement pour validation'],
            ['code' => 'PAIEMENTS_APPROVE', 'libelle' => 'Approuver / rejeter un paiement'],

            // ─── RAPPORTS ─────────────────────────────────────────────────────
            ['code' => 'RAPPORTS_READ',   'libelle' => 'Consulter les rapports'],
            ['code' => 'RAPPORTS_EXPORT', 'libelle' => 'Exporter les rapports (PDF, Excel)'],
            ['code' => 'REPORT_VIEW',     'libelle' => 'Voir les rapports'],
            ['code' => 'REPORT_EXPORT',   'libelle' => 'Exporter (PDF/Excel)'],
            ['code' => 'REPORT_FINANCIAL', 'libelle' => 'Rapports financiers et situation'],
            ['code' => 'REPORT_CONTRACT',  'libelle' => 'Rapports contrats'],

            // ─── UTILISATEURS ─────────────────────────────────────────────────
            ['code' => 'USERS_READ',         'libelle' => 'Consulter les utilisateurs'],
            ['code' => 'USERS_CREATE',        'libelle' => 'Créer des utilisateurs'],
            ['code' => 'USERS_EDIT',          'libelle' => 'Modifier les utilisateurs'],
            ['code' => 'USERS_DELETE',        'libelle' => 'Supprimer des utilisateurs'],
            ['code' => 'USERS_MANAGE_ROLES',  'libelle' => 'Assigner / révoquer les rôles des utilisateurs'],
            ['code' => 'USERS_MANAGE_STATUS', 'libelle' => 'Activer / suspendre / désactiver des comptes'],

            // ─── RÔLES ────────────────────────────────────────────────────────
            ['code' => 'ROLES_READ',   'libelle' => 'Consulter les rôles'],
            ['code' => 'ROLES_CREATE', 'libelle' => 'Créer des rôles'],
            ['code' => 'ROLES_EDIT',   'libelle' => 'Modifier des rôles'],
            ['code' => 'ROLES_DELETE', 'libelle' => 'Supprimer des rôles'],

            // ─── PERMISSIONS ─────────────────────────────────────────────────
            ['code' => 'PERMISSIONS_READ',   'libelle' => 'Consulter les permissions'],
            ['code' => 'PERMISSIONS_CREATE', 'libelle' => 'Créer des permissions'],
            ['code' => 'PERMISSIONS_EDIT',   'libelle' => 'Modifier des permissions'],
            ['code' => 'PERMISSIONS_DELETE', 'libelle' => 'Supprimer des permissions'],

            // ─── AUDIT ────────────────────────────────────────────────────────
            ['code' => 'AUDIT_READ',   'libelle' => 'Consulter les journaux d\'audit'],
            ['code' => 'AUDIT_EXPORT', 'libelle' => 'Exporter les journaux d\'audit'],

            // ─── SYSTÈME ─────────────────────────────────────────────────────
            ['code' => 'SYSTEM_CONFIG',   'libelle' => 'Configurer les paramètres système'],
            ['code' => 'SYSTEM_SECURITY', 'libelle' => 'Gérer les politiques de sécurité'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['code' => $permission['code']],
                ['libelle' => $permission['libelle']]
            );
        }

        $this->command->info(sprintf(
            'Permissions créées/vérifiées : %d permissions au total.',
            Permission::count()
        ));
    }
}
