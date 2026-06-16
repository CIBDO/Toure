# Module Ordres de Service (Phase 2) — Livrables

## Résumé

Module **Ordres de Service** rattaché aux contrats : création, suivi, workflow (draft → submitted → approved/rejected → executed), impact sur la date de fin du contrat, GED et audit.

---

## 1. Fichiers créés

### Backend (Laravel)

| Fichier | Description |
|---------|-------------|
| `database/migrations/2026_02_25_160001_create_ordre_services_table.php` | Table `ordre_services` (uuid, contrat_id, numero, type_os, objet, description, date_emission, date_effet, impact_delai, delai_jours, statut, commentaire_validation, issued_by, approved_by, approved_at, executed_at, created_by, timestamps, softDeletes) |
| `app/Models/OrdreService.php` | Modèle avec constantes (type_os, impact_delai, statut), relations contrat, documents, issuedBy, approvedBy, createdBy, helpers (isDraft, isPendingValidation, isApproved, isExecuted) |
| `app/Services/OrdreServiceService.php` | generateNumero(Contrat), validateBusinessRules(OrdreService), approveOs(OrdreService), executeOs(OrdreService), simulateNewDateFin(...) |
| `app/Http/Requests/StoreOrdreServiceRequest.php` | Validation création : type_os, objet, date_emission, règles suspension (description + date_effet), extend/reduce (delai_jours) |
| `app/Http/Requests/UpdateOrdreServiceRequest.php` | Validation mise à jour (brouillon) |
| `app/Http/Requests/RejectOrdreServiceRequest.php` | Validation rejet (commentaire_validation optionnel) |
| `app/Policies/OrdreServicePolicy.php` | viewAny, view, create, update, delete, submit, approve, reject, execute (permissions OS_* et CONTRATS_*) |
| `app/Http/Controllers/Api/OrdreServiceController.php` | index, indexByContrat, store, show, update, destroy, submit, approve, reject, execute + AuditLog sur chaque action |
| `tests/Feature/Api/OrdreServiceTest.php` | Tests : create OS, approve OS (extend → date_fin contrat), reject, execute, cannot approve on archived contract, permissions enforcement |

### Frontend (Vue 3 + Vuexy)

| Fichier | Description |
|---------|-------------|
| `resources/ts/stores/ordreServices.ts` | Store Pinia : fetchOrdreServices, fetchOrdreServicesByContrat, fetchOrdreService, createOrdreService, updateOrdreService, deleteOrdreService, submitOrdreService, approveOrdreService, rejectOrdreService, executeOrdreService |
| `resources/ts/components/contracts/OrdreServiceForm.vue` | Formulaire : type_os, objet, description, date_emission, date_effet, impact_delai, delai_jours + simulation nouvelle date_fin |
| `resources/ts/pages/apps/contrats/ordre-services/index.vue` | Liste OS : filtres (contrat, type, statut, dates), DataTableServer, actions (Voir, Modifier, Soumettre, Approuver, Rejeter, Exécuter, Supprimer), dialogs formulaire / rejet / suppression |
| `resources/ts/pages/apps/contrats/ordre-services/[id].vue` | Détail OS : infos + DocumentsPanel (GED) pour type `ordre_services` |

### Documentation

| Fichier | Description |
|---------|-------------|
| `docs/ORDRE-SERVICE-LIVRABLES.md` | Ce fichier |

---

## 2. Fichiers modifiés

| Fichier | Modifications |
|---------|----------------|
| `app/Models/Contrat.php` | Relation `ordreServices()` hasMany OrdreService |
| `config/ged.php` | `documentable_types['ordre_services'] = OrdreService::class`, catégories `os_signe`, `piece_justificative` |
| `database/seeders/PermissionSeeder.php` | Ajout permissions OS_READ, OS_CREATE, OS_EDIT, OS_DELETE, OS_SUBMIT, OS_APPROVE, OS_EXECUTE |
| `database/seeders/RoleSeeder.php` | Attribution des permissions OS_* (et AVENANTS_* où manquant) aux rôles ADMIN_CANAM, DIRECTEUR, SUPERVISEUR, AGENT_CONTRAT, LECTEUR |
| `routes/api.php` | Routes GET/POST ordre-services, GET/POST/PUT/DELETE par contrat et par id + submit, approve, reject, execute |
| `app/Http/Controllers/Api/AuthController.php` | subjectMap `'OS' => 'OrdreService'`, `'AVENANTS' => 'Contrat'` pour CASL |
| `resources/ts/plugins/casl/ability.ts` | Subject `OrdreService` |
| `resources/ts/services/documentService.ts` | GED_ENTITY_OPTIONS `ordre_services`, GED_CATEGORIES `os_signe`, `piece_justificative` |
| `resources/ts/navigation/vertical/canam.ts` | Entrée menu « Ordres de service » → `apps-contrats-ordre-services`, subject `OrdreService` |
| `resources/ts/pages/apps/contrats/[id].vue` | Onglet « Ordres de service », store ordreServices, loadOrdreServices, tableau OS, bouton « Nouvel OS », dialog OrdreServiceForm, snackbar OS |

---

## 3. Routes API

| Méthode | URI | Action |
|--------|-----|--------|
| GET | `/api/ordre-services` | Liste globale (filtres : contrat_id, statut, type_os, date_emission_from/to) |
| GET | `/api/contrats/{contrat}/ordre-services` | Liste par contrat |
| POST | `/api/contrats/{contrat}/ordre-services` | Créer un OS |
| GET | `/api/ordre-services/{ordre_service}` | Détail OS |
| PUT | `/api/ordre-services/{ordre_service}` | Modifier (brouillon) |
| DELETE | `/api/ordre-services/{ordre_service}` | Supprimer (brouillon) |
| POST | `/api/ordre-services/{ordre_service}/submit` | Soumettre |
| POST | `/api/ordre-services/{ordre_service}/approve` | Approuver (applique impact délai sur contrat) |
| POST | `/api/ordre-services/{ordre_service}/reject` | Rejeter |
| POST | `/api/ordre-services/{ordre_service}/execute` | Marquer exécuté |

---

## 4. Permissions RBAC

- `OS_READ` — Consulter les ordres de service  
- `OS_CREATE` — Créer un ordre de service  
- `OS_EDIT` — Modifier un ordre de service  
- `OS_DELETE` — Supprimer un ordre de service  
- `OS_SUBMIT` — Soumettre pour validation  
- `OS_APPROVE` — Approuver / rejeter  
- `OS_EXECUTE` — Marquer comme exécuté  

---

## 5. Règles métier

- Numéro auto : format `OS-{année}-{contract_num}-{sequence}` (ex. OS-2026-C-2026-001-003).
- Un OS ne peut pas être approuvé si le contrat est archivé.
- OS de type **suspension** : description et date_effet obligatoires.
- **Impact délai** : si `impact_delai` = extend/reduce, `delai_jours` obligatoire > 0 ; à l’approbation, `date_fin` du contrat est mise à jour en transaction.
- Workflow : draft → submitted → approved | rejected ; si approved → executable (bouton Exécuter) → executed.
- GED : documents polymorphiques `documentable_type = App\Models\OrdreService`, catégories `os_signe`, `piece_justificative`, `autres`.

---

## 6. Commandes d’installation / exécution

```bash
# Migrations
php artisan migrate

# Permissions + rôles (OS_* et AVENANTS_*)
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=RoleSeeder

# Tests (nécessite une base de test configurée, ex. .env.testing avec DB_DATABASE=testing)
php artisan test tests/Feature/Api/OrdreServiceTest.php
```

---

## 7. Points d’attention

- **Réceptions** : non traitées dans ce module (prévu séparément).
- **Téléchargement GED** : via API sécurisée `/api/documents/{id}/download`, pas de lien public.
- **Audit** : toutes les actions (create, update, delete, submit, approve, reject, execute) sont enregistrées dans `audit_logs` avec ancienne/nouvelle valeur.
- Les tests feature utilisent `RefreshDatabase` ; la base de test doit exister (ou utiliser SQLite en mémoire dans `phpunit.xml`).
