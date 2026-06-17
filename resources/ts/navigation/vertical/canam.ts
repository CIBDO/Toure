/**
 * CANAM Contract Manager — Menu de navigation vertical
 *
 * Structure :
 *   - Chaque item de lien porte { action, subject } correspondant aux règles CASL.
 *   - Les groupes sans action/subject propres sont visibles si au moins un enfant l'est
 *     (logique gérée par canViewNavMenuGroup dans @layouts/plugins/casl).
 *   - Les items Phase 2 sont commentés ; décommenter quand les routes seront créées.
 *
 * Convention permissions backend → CASL :
 *   "avis.view"  →  { action: 'view', subject: 'Avis' }
 *   "contrat.validate" → { action: 'validate', subject: 'Contrat' }
 */

import type { VerticalNavItems } from '@layouts/types'

const canamnNavItems: VerticalNavItems = [
  // ─────────────────────────────────────────────────────────────
  // En-tête de section
  // ─────────────────────────────────────────────────────────────
  { heading: 'CANAM Contract Manager' },

  // ─────────────────────────────────────────────────────────────
  // 1. Dashboard
  // ─────────────────────────────────────────────────────────────
  {
    title: 'Tableau de bord',
    icon: { icon: 'tabler-layout-dashboard' },
    to: 'dashboards-crm',
    action: 'view',
    subject: 'Dashboard',
  },

  // ─────────────────────────────────────────────────────────────
  // 2. Passation des marchés
  // ─────────────────────────────────────────────────────────────
  {
    title: 'Passation',
    icon: { icon: 'tabler-file-description' },
    children: [
      {
        title: 'Avis de passation',
        icon: { icon: 'tabler-file-text' },
        to: 'apps-passation-avis',
        action: 'view',
        subject: 'Avis',
      },
      {
        title: 'Ouverture des plis',
        icon: { icon: 'tabler-clipboard-list' },
        to: 'apps-passation-depouillements',
        action: 'view',
        subject: 'Depouillement',
      },
      {
        title: 'Procès-Verbaux',
        icon: { icon: 'tabler-file-check' },
        to: 'apps-passation-pvs',
        action: 'view',
        subject: 'Pv',
      },
    ],
  },

  // ─────────────────────────────────────────────────────────────
  // 3. Contrats
  // ─────────────────────────────────────────────────────────────
  {
    title: 'Contrats',
    icon: { icon: 'tabler-file-check' },
    children: [
      {
        title: 'Contrats',
        icon: { icon: 'tabler-files' },
        to: 'apps-contrats',
        action: 'view',
        subject: 'Contrat',
      },
      {
        title: 'Avenants',
        icon: { icon: 'tabler-file-plus' },
        to: 'apps-contrats-avenants',
        action: 'view',
        subject: 'Contrat',
      },
      {
        title: 'Ordres de service',
        icon: { icon: 'tabler-clipboard-text' },
        to: 'apps-contrats-ordre-services',
        action: 'view',
        subject: 'OrdreService',
      },
      {
        title: 'Réceptions',
        icon: { icon: 'tabler-package-import' },
        to: 'apps-contrats-receptions',
        action: 'view',
        subject: 'Contrat',
      },
    ],
  },

  // ─────────────────────────────────────────────────────────────
  // 4. Finances
  // ─────────────────────────────────────────────────────────────
  {
    title: 'Finances',
    icon: { icon: 'tabler-coin' },
    children: [
      {
        title: 'Engagements',
        icon: { icon: 'tabler-receipt' },
        to: 'apps-finances-engagements',
        action: 'view',
        subject: 'Engagement',
      },
      {
        title: 'Paiements',
        icon: { icon: 'tabler-credit-card' },
        to: 'apps-finances-paiements',
        action: 'view',
        subject: 'Paiement',
      },
      // Phase 2 — décommenter quand les routes seront disponibles
      // {
      //   title: 'Factures',
      //   icon: { icon: 'tabler-file-invoice' },
      //   to: 'apps-finances-factures',
      //   action: 'view',
      //   subject: 'Paiement',
      // },
      // {
      //   title: 'Mandats',
      //   icon: { icon: 'tabler-file-dollar' },
      //   to: 'apps-finances-mandats',
      //   action: 'view',
      //   subject: 'Paiement',
      // },
      // {
      //   title: 'Avances & Garanties',
      //   icon: { icon: 'tabler-shield-dollar' },
      //   to: 'apps-finances-avances-garanties',
      //   action: 'view',
      //   subject: 'Paiement',
      // },
    ],
  },

  // ─────────────────────────────────────────────────────────────
  // 5. Documents (GED)
  // ─────────────────────────────────────────────────────────────
  {
    title: 'Documents',
    icon: { icon: 'tabler-folder-open' },
    children: [
      {
        title: 'GED',
        icon: { icon: 'tabler-archive' },
        to: 'apps-documents-ged',
        action: 'view',
        subject: 'Document',
      },
    ],
  },

  // ─────────────────────────────────────────────────────────────
  // 6. Rapports
  // ─────────────────────────────────────────────────────────────
  {
    title: 'Rapports',
    icon: { icon: 'tabler-chart-bar' },
    children: [
      {
        title: 'Synthèse Contrats',
        icon: { icon: 'tabler-report' },
        to: 'apps-rapports-contrats',
        action: 'view',
        subject: 'Report',
      },
      {
        title: 'Situation Financière',
        icon: { icon: 'tabler-report-money' },
        to: 'apps-rapports-financial',
        action: 'view',
        subject: 'Report',
      },
      {
        title: 'Engagements',
        icon: { icon: 'tabler-receipt' },
        to: 'apps-rapports-engagements',
        action: 'view',
        subject: 'Report',
      },
      {
        title: 'Paiements',
        icon: { icon: 'tabler-credit-card' },
        to: 'apps-rapports-payments',
        action: 'view',
        subject: 'Report',
      },
      {
        title: 'Performance Fournisseurs',
        icon: { icon: 'tabler-building-store' },
        to: 'apps-rapports-suppliers',
        action: 'view',
        subject: 'Report',
      },
    ],
  },

  // ─────────────────────────────────────────────────────────────
  // En-tête de section Administration
  // ─────────────────────────────────────────────────────────────
  { heading: 'Administration' },

  // ─────────────────────────────────────────────────────────────
  // 7. Administration
  // ─────────────────────────────────────────────────────────────
  {
    title: 'Utilisateurs',
    icon: { icon: 'tabler-users' },
    children: [
      {
        title: 'Liste des utilisateurs',
        icon: { icon: 'tabler-user-circle' },
        to: 'apps-user-list',
        action: 'manage',
        subject: 'User',
      },
      {
        title: 'Profils & Permissions',
        icon: { icon: 'tabler-shield-check' },
        to: 'apps-roles',
        action: 'manage',
        subject: 'Role',
      },
      {
        title: 'Permissions',
        icon: { icon: 'tabler-shield-lock' },
        to: 'apps-permissions',
        action: 'manage',
        subject: 'Permission',
      },
    ],
  },

  {
    title: 'Référentiels',
    icon: { icon: 'tabler-database' },
    children: [
      {
        title: 'Fournisseurs',
        icon: { icon: 'tabler-building-store' },
        to: 'apps-referentiels-fournisseurs',
        action: 'view',
        subject: 'Fournisseur',
      },
      {
        title: 'Banques',
        icon: { icon: 'tabler-building-bank' },
        to: 'apps-referentiels-banques',
        action: 'manage',
        subject: 'Reference',
      },
      {
        title: "Domaines d'activité",
        icon: { icon: 'tabler-category' },
        to: 'apps-referentiels-domaines',
        action: 'manage',
        subject: 'Reference',
      },
      {
        title: 'Expressions de besoin',
        icon: { icon: 'tabler-list-check' },
        to: 'apps-referentiels-expressions-besoin',
        action: 'manage',
        subject: 'Reference',
      },
      {
        title: 'Comptes budget',
        icon: { icon: 'tabler-wallet' },
        to: 'apps-referentiels-comptes-budget',
        action: 'manage',
        subject: 'Reference',
      },
      // Phase 2 — décommenter quand la route sera disponible
      // {
      //   title: 'Paramétrages généraux',
      //   icon: { icon: 'tabler-adjustments' },
      //   to: 'apps-referentiels-parametrages',
      //   action: 'manage',
      //   subject: 'Reference',
      // },
    ],
  },
]

export default canamnNavItems
