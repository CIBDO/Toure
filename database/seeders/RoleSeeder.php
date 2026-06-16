<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Seeder pour les rôles CANAM
 *
 * Hiérarchie des rôles :
 *
 *  1. ADMIN_CANAM          → Administrateur système (toutes permissions)
 *  2. DIRECTEUR            → Directeur (approbation finale, lecture globale)
 *  3. SUPERVISEUR          → Superviseur passation & contrats (workflow complet)
 *  4. AGENT_PASSATION      → Agent de passation des marchés
 *  5. AGENT_CONTRAT        → Agent de gestion des contrats
 *  6. AGENT_FINANCIER      → Agent financier (engagements, paiements)
 *  7. LECTEUR              → Consultation uniquement (lecture seule)
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Charger toutes les permissions en mémoire (évite N+1)
        $allPermissions = Permission::all()->keyBy('code');

        $roles = $this->getRolesDefinition();

        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(
                ['code' => $roleData['code']],
                ['libelle' => $roleData['libelle']]
            );

            // Mettre à jour le libellé si le rôle existait déjà
            if (!$role->wasRecentlyCreated) {
                $role->update(['libelle' => $roleData['libelle']]);
            }

            // Résoudre les IDs des permissions
            $permissionIds = collect($roleData['permissions'])
                ->map(fn($code) => $allPermissions->get($code)?->id)
                ->filter()
                ->values()
                ->toArray();

            $role->permissions()->sync($permissionIds);

            $this->command->info(sprintf(
                '  [%s] %s → %d permissions assignées',
                $role->code,
                $role->libelle,
                count($permissionIds)
            ));
        }

        $this->command->info(sprintf(
            'Rôles créés/mis à jour : %d rôles au total.',
            Role::count()
        ));
    }

    /**
     * Définition des rôles et de leurs permissions.
     */
    private function getRolesDefinition(): array
    {
        return [

            // ─────────────────────────────────────────────────────────────────
            // 1. ADMINISTRATEUR CANAM
            //    Accès total au système (gestion IAM, configuration, audit)
            // ─────────────────────────────────────────────────────────────────
            [
                'code'    => 'ADMIN_CANAM',
                'libelle' => 'Administrateur CANAM',
                'permissions' => [
                    'DASHBOARD_READ',
                    // Passation complète
                    'AVIS_READ', 'AVIS_CREATE', 'AVIS_EDIT', 'AVIS_DELETE',
                    'AVIS_SUBMIT', 'AVIS_APPROVE', 'AVIS_REJECT', 'AVIS_PUBLISH', 'AVIS_CLOSE',
                    'DEPOUILLEMENTS_READ', 'DEPOUILLEMENTS_CREATE', 'DEPOUILLEMENTS_EDIT', 'DEPOUILLEMENTS_DELETE',
                    'DEPOUILLEMENTS_SUBMIT', 'DEPOUILLEMENTS_APPROVE', 'DEPOUILLEMENTS_REJECT',
                    'PVS_READ', 'PVS_CREATE', 'PVS_EDIT', 'PVS_DELETE',
                    'PVS_SUBMIT', 'PVS_APPROVE', 'PVS_REJECT', 'PVS_GENERATE_PDF', 'PVS_UPLOAD_SIGNE',
                    // Contrats complet
                    'CONTRATS_READ', 'CONTRATS_CREATE', 'CONTRATS_EDIT', 'CONTRATS_DELETE',
                    'CONTRATS_SUBMIT', 'CONTRATS_APPROVE', 'CONTRATS_REJECT', 'CONTRATS_ARCHIVE', 'CONTRATS_ETAPES',
                    'AVENANTS_READ', 'AVENANTS_CREATE', 'AVENANTS_EDIT', 'AVENANTS_DELETE',
                    'AVENANTS_SUBMIT', 'AVENANTS_APPROVE',
                    'OS_READ', 'OS_CREATE', 'OS_EDIT', 'OS_DELETE', 'OS_SUBMIT', 'OS_APPROVE', 'OS_EXECUTE',
                    // Référentiels & fournisseurs
                    'FOURNISSEURS_READ', 'FOURNISSEURS_CREATE', 'FOURNISSEURS_EDIT', 'FOURNISSEURS_DELETE',
                    'REFERENTIELS_READ', 'REFERENTIELS_CREATE', 'REFERENTIELS_EDIT', 'REFERENTIELS_DELETE',
                    // GED
                    'GED_READ', 'GED_EDIT', 'GED_UPLOAD', 'GED_DOWNLOAD', 'GED_DELETE',
                    // Finances
                    'FINANCES_READ', 'FINANCES_CREATE', 'FINANCES_EDIT', 'FINANCES_APPROVE',
                    'ENGAGEMENTS_READ', 'ENGAGEMENTS_CREATE', 'ENGAGEMENTS_EDIT', 'ENGAGEMENTS_DELETE',
                    'ENGAGEMENTS_SUBMIT', 'ENGAGEMENTS_APPROVE',
                    'PAIEMENTS_READ', 'PAIEMENTS_CREATE', 'PAIEMENTS_EDIT', 'PAIEMENTS_DELETE',
                    'PAIEMENTS_SUBMIT', 'PAIEMENTS_APPROVE',
                    // Rapports
                    'RAPPORTS_READ', 'RAPPORTS_EXPORT',
                    // IAM
                    'USERS_READ', 'USERS_CREATE', 'USERS_EDIT', 'USERS_DELETE',
                    'USERS_MANAGE_ROLES', 'USERS_MANAGE_STATUS',
                    'ROLES_READ', 'ROLES_CREATE', 'ROLES_EDIT', 'ROLES_DELETE',
                    'PERMISSIONS_READ', 'PERMISSIONS_CREATE', 'PERMISSIONS_EDIT', 'PERMISSIONS_DELETE',
                    // Audit & Système
                    'AUDIT_READ', 'AUDIT_EXPORT',
                    'SYSTEM_CONFIG', 'SYSTEM_SECURITY',
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // 2. DIRECTEUR
            //    Approbation finale, lecture globale, rapports, pas de saisie
            // ─────────────────────────────────────────────────────────────────
            [
                'code'    => 'DIRECTEUR',
                'libelle' => 'Directeur',
                'permissions' => [
                    'DASHBOARD_READ',
                    'AVIS_READ', 'AVIS_APPROVE', 'AVIS_REJECT', 'AVIS_PUBLISH', 'AVIS_CLOSE',
                    'DEPOUILLEMENTS_READ', 'DEPOUILLEMENTS_APPROVE', 'DEPOUILLEMENTS_REJECT',
                    'PVS_READ', 'PVS_APPROVE', 'PVS_REJECT', 'PVS_GENERATE_PDF',
                    'CONTRATS_READ', 'CONTRATS_APPROVE', 'CONTRATS_REJECT', 'CONTRATS_ARCHIVE',
                    'AVENANTS_READ', 'AVENANTS_APPROVE',
                    'OS_READ', 'OS_APPROVE',
                    'FOURNISSEURS_READ',
                    'REFERENTIELS_READ',
                    'GED_READ', 'GED_DOWNLOAD',
                    'FINANCES_READ', 'FINANCES_APPROVE',
                    'ENGAGEMENTS_READ', 'ENGAGEMENTS_APPROVE',
                    'PAIEMENTS_READ', 'PAIEMENTS_APPROVE',
                    'RAPPORTS_READ', 'RAPPORTS_EXPORT',
                    'USERS_READ',
                    'AUDIT_READ', 'AUDIT_EXPORT',
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // 3. SUPERVISEUR PASSATION & CONTRATS
            //    Pilote tout le workflow passation + contrats, gère les agents
            // ─────────────────────────────────────────────────────────────────
            [
                'code'    => 'SUPERVISEUR',
                'libelle' => 'Superviseur Passation & Contrats',
                'permissions' => [
                    'DASHBOARD_READ',
                    'AVIS_READ', 'AVIS_CREATE', 'AVIS_EDIT', 'AVIS_DELETE',
                    'AVIS_SUBMIT', 'AVIS_APPROVE', 'AVIS_REJECT', 'AVIS_PUBLISH', 'AVIS_CLOSE',
                    'DEPOUILLEMENTS_READ', 'DEPOUILLEMENTS_CREATE', 'DEPOUILLEMENTS_EDIT',
                    'DEPOUILLEMENTS_SUBMIT', 'DEPOUILLEMENTS_APPROVE', 'DEPOUILLEMENTS_REJECT',
                    'PVS_READ', 'PVS_CREATE', 'PVS_EDIT',
                    'PVS_SUBMIT', 'PVS_APPROVE', 'PVS_REJECT', 'PVS_GENERATE_PDF', 'PVS_UPLOAD_SIGNE',
                    'CONTRATS_READ', 'CONTRATS_CREATE', 'CONTRATS_EDIT',
                    'CONTRATS_SUBMIT', 'CONTRATS_APPROVE', 'CONTRATS_REJECT', 'CONTRATS_ARCHIVE', 'CONTRATS_ETAPES',
                    'AVENANTS_READ', 'AVENANTS_CREATE', 'AVENANTS_EDIT', 'AVENANTS_DELETE',
                    'AVENANTS_SUBMIT', 'AVENANTS_APPROVE',
                    'OS_READ', 'OS_CREATE', 'OS_EDIT', 'OS_DELETE', 'OS_SUBMIT', 'OS_APPROVE', 'OS_EXECUTE',
                    'FOURNISSEURS_READ', 'FOURNISSEURS_CREATE', 'FOURNISSEURS_EDIT',
                    'REFERENTIELS_READ', 'REFERENTIELS_CREATE', 'REFERENTIELS_EDIT',
                    'GED_READ', 'GED_EDIT', 'GED_UPLOAD', 'GED_DOWNLOAD',
                    'FINANCES_READ',
                    'ENGAGEMENTS_READ', 'ENGAGEMENTS_CREATE', 'ENGAGEMENTS_EDIT', 'ENGAGEMENTS_SUBMIT',
                    'PAIEMENTS_READ', 'PAIEMENTS_CREATE', 'PAIEMENTS_EDIT', 'PAIEMENTS_SUBMIT',
                    'RAPPORTS_READ', 'RAPPORTS_EXPORT',
                    'USERS_READ',
                    'AUDIT_READ',
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // 4. AGENT DE PASSATION
            //    Saisit et soumet les avis, dépouillements et PV
            // ─────────────────────────────────────────────────────────────────
            [
                'code'    => 'AGENT_PASSATION',
                'libelle' => 'Agent de Passation des Marchés',
                'permissions' => [
                    'DASHBOARD_READ',
                    'AVIS_READ', 'AVIS_CREATE', 'AVIS_EDIT', 'AVIS_SUBMIT',
                    'DEPOUILLEMENTS_READ', 'DEPOUILLEMENTS_CREATE', 'DEPOUILLEMENTS_EDIT', 'DEPOUILLEMENTS_SUBMIT',
                    'PVS_READ', 'PVS_CREATE', 'PVS_EDIT', 'PVS_SUBMIT', 'PVS_GENERATE_PDF',
                    'CONTRATS_READ',
                    'FOURNISSEURS_READ', 'FOURNISSEURS_CREATE', 'FOURNISSEURS_EDIT',
                    'REFERENTIELS_READ',
                    'GED_READ', 'GED_EDIT', 'GED_UPLOAD', 'GED_DOWNLOAD',
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // 5. AGENT CONTRATS
            //    Saisit et suit les contrats et leurs étapes d'exécution
            // ─────────────────────────────────────────────────────────────────
            [
                'code'    => 'AGENT_CONTRAT',
                'libelle' => 'Agent Gestion des Contrats',
                'permissions' => [
                    'DASHBOARD_READ',
                    'AVIS_READ',
                    'DEPOUILLEMENTS_READ',
                    'PVS_READ',
                    'CONTRATS_READ', 'CONTRATS_CREATE', 'CONTRATS_EDIT', 'CONTRATS_SUBMIT', 'CONTRATS_ETAPES',
                    'AVENANTS_READ', 'AVENANTS_CREATE', 'AVENANTS_EDIT', 'AVENANTS_DELETE', 'AVENANTS_SUBMIT',
                    'OS_READ', 'OS_CREATE', 'OS_EDIT', 'OS_DELETE', 'OS_SUBMIT', 'OS_EXECUTE',
                    'FOURNISSEURS_READ',
                    'REFERENTIELS_READ',
                    'GED_READ', 'GED_EDIT', 'GED_UPLOAD', 'GED_DOWNLOAD',
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // 6. AGENT FINANCIER
            //    Gère les engagements et paiements liés aux contrats
            // ─────────────────────────────────────────────────────────────────
            [
                'code'    => 'AGENT_FINANCIER',
                'libelle' => 'Agent Financier',
                'permissions' => [
                    'DASHBOARD_READ',
                    'CONTRATS_READ',
                    'FOURNISSEURS_READ',
                    'REFERENTIELS_READ',
                    'GED_READ', 'GED_DOWNLOAD',
                    'FINANCES_READ', 'FINANCES_CREATE', 'FINANCES_EDIT',
                    'ENGAGEMENTS_READ', 'ENGAGEMENTS_CREATE', 'ENGAGEMENTS_EDIT', 'ENGAGEMENTS_SUBMIT',
                    'PAIEMENTS_READ', 'PAIEMENTS_CREATE', 'PAIEMENTS_EDIT', 'PAIEMENTS_SUBMIT',
                    'RAPPORTS_READ', 'RAPPORTS_EXPORT',
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // 7. LECTEUR
            //    Consultation uniquement, aucune modification
            // ─────────────────────────────────────────────────────────────────
            [
                'code'    => 'LECTEUR',
                'libelle' => 'Lecteur (consultation uniquement)',
                'permissions' => [
                    'DASHBOARD_READ',
                    'AVIS_READ',
                    'DEPOUILLEMENTS_READ',
                    'PVS_READ',
                    'CONTRATS_READ',
                    'AVENANTS_READ',
                    'OS_READ',
                    'FOURNISSEURS_READ',
                    'REFERENTIELS_READ',
                    'GED_READ', 'GED_DOWNLOAD',
                    'FINANCES_READ',
                    'ENGAGEMENTS_READ',
                    'PAIEMENTS_READ',
                    'RAPPORTS_READ',
                    'AUDIT_READ',
                ],
            ],

        ];
    }
}
