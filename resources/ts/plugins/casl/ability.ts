import { createMongoAbility } from '@casl/ability'

/**
 * Actions CASL pour CANAM Contract Manager
 * Correspondent aux verbes des permissions backend (ex: avis.view → action: 'view')
 */
export type Actions =
  | 'view'
  | 'create'
  | 'update'
  | 'delete'
  | 'download'
  | 'validate'
  | 'manage'
  | 'read'
  | 'submit'
  | 'approve'

/**
 * Sujets CASL pour CANAM Contract Manager
 * Correspondent aux ressources des permissions backend (ex: avis.view → subject: 'Avis')
 *
 * Convention : PascalCase, correspond au nom de la ressource backend
 */
export type Subjects =
  | 'Dashboard'
  | 'Avis'
  | 'Depouillement'
  | 'Pv'
  | 'Contrat'
  | 'OrdreService'
  | 'Engagement'
  | 'Paiement'
  | 'Document'
  | 'Fournisseur'
  | 'Banque'
  | 'DomaineActivite'
  | 'CompteBudget'
  | 'User'
  | 'Role'
  | 'Permission'
  | 'Reference'
  | 'Report'
  | 'all'

export interface Rule { action: Actions; subject: Subjects }

export const ability = createMongoAbility<[Actions, Subjects]>()
