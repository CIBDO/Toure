# CANAM Contract Manager

Application web de gestion intégrée des contrats pour la CANAM (Caisse Nationale d'Assurance Maladie).

## Stack Technique

| Couche | Technologie |
|--------|-------------|
| Backend | Laravel 11 (PHP 8.2+) |
| Auth | Laravel Sanctum |
| RBAC | Système de rôles/permissions custom |
| Base de données | MySQL / PostgreSQL |
| Frontend | Vue.js 3 + Vuexy Template |
| State | Pinia |
| UI | Vuetify 3 + DataTables server-side |
| Build | Vite 5 |

---

## Architecture des modules

```
CANAM/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── AuthController.php          # Authentification
│   │   │   ├── UserController.php          # Gestion utilisateurs
│   │   │   ├── RoleController.php          # Gestion rôles
│   │   │   ├── PermissionController.php    # Gestion permissions
│   │   │   ├── BanqueController.php        # Référentiel banques
│   │   │   ├── DomaineActiviteController.php # Domaines d'activité
│   │   │   ├── CompteBudgetController.php  # Comptes budget
│   │   │   ├── FournisseurController.php   # Fournisseurs
│   │   │   ├── AvisController.php          # Avis de passation
│   │   │   ├── DepouillementController.php # Dépouillements
│   │   │   ├── PvController.php            # Procès-verbaux
│   │   │   ├── ContratController.php       # Contrats
│   │   │   ├── PieceJointeController.php   # GED documents
│   │   │   └── DashboardController.php     # Statistiques
│   │   └── Requests/                       # FormRequests validation
│   └── Models/
│       ├── User.php, Role.php, Permission.php
│       ├── AuditLog.php                    # Logs d'audit
│       ├── Banque.php, DomaineActivite.php, CompteBudget.php
│       ├── Fournisseur.php, FournisseurBanque.php
│       ├── Avis.php, AvisItem.php
│       ├── Depouillement.php, Pv.php
│       ├── Contrat.php, ContratEtape.php
│       └── PieceJointe.php
├── database/
│   ├── migrations/                         # 13 nouvelles migrations
│   └── seeders/
│       ├── PermissionSeeder.php
│       ├── RoleSeeder.php
│       ├── BanqueSeeder.php
│       ├── DomaineActiviteSeeder.php
│       ├── CompteBudgetSeeder.php
│       ├── FournisseurSeeder.php
│       └── DemoDataSeeder.php
├── resources/ts/
│   ├── pages/apps/
│   │   ├── referentiels/                   # Banques, Domaines, Comptes, Fournisseurs
│   │   ├── passation/                      # Avis, Dépouillements, PV
│   │   └── contrats/                       # Liste + Détail contrat
│   ├── stores/                             # Pinia stores
│   └── navigation/vertical/canam.ts       # Menu de navigation
└── tests/Feature/Api/                      # Tests fonctionnels
    ├── AuthTest.php
    ├── RbacTest.php
    ├── AvisTest.php
    ├── FournisseurTest.php
    └── ContratTest.php
```

---

## Modèles et Relations

```
User ──< Role >── Permission
  │
  └──< AuditLog

Banque ──< FournisseurBanque >── Fournisseur
DomaineActivite ──< Fournisseur

Fournisseur ──< Avis (via avis_fournisseurs)
Avis ──< AvisItem
Avis ──< Depouillement
Avis ──< Pv
Depouillement ──< Pv
Pv ──< Contrat
Contrat ──< ContratEtape (5 étapes: elaboration, engagement, oem, mandat, paie)

Avis, Pv, Contrat ──< PieceJointe (polymorphique)
CompteBudget ──< Contrat
User (agent) ──< Contrat
```

---

## Prérequis

- PHP >= 8.2
- Composer
- Node.js >= 18
- MySQL 8+ ou PostgreSQL 14+
- Extension PHP: pdo, pdo_mysql/pdo_pgsql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath

---

## Installation

### 1. Cloner et installer les dépendances

```bash
git clone <repo-url> canam
cd canam

composer install
npm install
```

### 2. Configurer l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

Éditer `.env` :

```env
APP_NAME="CANAM Contract Manager"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=canam_db
DB_USERNAME=root
DB_PASSWORD=

# Pour PostgreSQL :
# DB_CONNECTION=pgsql
# DB_PORT=5432

VITE_API_BASE_URL=http://localhost:8000/api
```

### 3. Créer la base de données

```bash
# MySQL
mysql -u root -e "CREATE DATABASE canam_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# PostgreSQL
psql -U postgres -c "CREATE DATABASE canam_db;"
```

### 4. Migrations et seeders

```bash
php artisan migrate
php artisan db:seed
```

Cela va créer :
- Toutes les tables (users, roles, permissions, banques, fournisseurs, avis, contrats, etc.)
- Les permissions et rôles de base
- 8 banques maliennes
- 15 domaines d'activité
- 5 comptes budget 2026
- 5 fournisseurs de démonstration
- Des données de démonstration (avis, dépouillements, PV, contrats)

### 5. Créer un utilisateur admin

```bash
php artisan tinker
```

```php
$admin = App\Models\User::create([
    'name'         => 'Admin CANAM',
    'nom'          => 'Admin',
    'prenom'       => 'CANAM',
    'email'        => 'admin@canam.ml',
    'password'     => bcrypt('Admin@2026!'),
    'statut'       => 'ACTIF',
    'type_compte'  => 'SYSTEME',
]);

$adminRole = App\Models\Role::where('code', 'ADMIN_DM')->first();
$admin->roles()->attach($adminRole);

echo "Admin créé : admin@canam.ml / Admin@2026!\n";
```

### 6. Lancer les serveurs

```bash
# Terminal 1 - Backend Laravel
php artisan serve

# Terminal 2 - Frontend Vite
npm run dev
```

L'application sera accessible sur : **http://localhost:5173**
L'API sera accessible sur : **http://localhost:8000/api**

---

## Rôles disponibles

| Code | Libellé | Accès |
|------|---------|-------|
| `ADMIN_DM` | Administrateur | Toutes les permissions |
| `SUPERVISEUR` | Superviseur | Passation + Contrats + Approbation |
| `AGENT_PASSATION` | Agent de Passation | Avis + Dépouillements + PV + Contrats (écriture) |
| `AGENT_CONTRAT` | Agent Contrats | Contrats (écriture) + lecture passation |
| `LECTEUR` | Lecteur | Lecture seule sur tout |

---

## Cycle de vie d'un contrat

```
Avis (draft → published → closed)
  └─> Dépouillement (draft → submitted → approved)
        └─> PV (draft → submitted → approved)
              └─> Contrat (draft → submitted → approved → archived)
                    └─> Étapes: elaboration → engagement → oem → mandat → paie
```

---

## API Endpoints principaux

```
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me

GET    /api/dashboard/stats

GET/POST       /api/banques
GET/PUT/DELETE /api/banques/{id}

GET/POST       /api/domaines
GET/POST       /api/comptes-budget
GET/POST       /api/fournisseurs

GET/POST       /api/avis
POST           /api/avis/{id}/publish
POST           /api/avis/{id}/close

GET/POST       /api/depouillements
POST           /api/depouillements/{id}/approve

GET/POST       /api/pvs
POST           /api/pvs/{id}/approve
GET            /api/pvs/{id}/pdf

GET/POST       /api/contrats
POST           /api/contrats/{id}/approve
POST           /api/contrats/{id}/archive
PUT            /api/contrats/{id}/etapes/{etapeId}

GET/POST       /api/pieces-jointes
GET            /api/pieces-jointes/{id}/download
DELETE         /api/pieces-jointes/{id}

GET/POST       /api/users
GET/POST       /api/roles
GET/POST       /api/permissions
```

---

## Tests

```bash
# Lancer tous les tests
php artisan test

# Tests spécifiques
php artisan test tests/Feature/Api/AuthTest.php
php artisan test tests/Feature/Api/RbacTest.php
php artisan test tests/Feature/Api/AvisTest.php
php artisan test tests/Feature/Api/FournisseurTest.php
php artisan test tests/Feature/Api/ContratTest.php
```

---

## Build production

```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Extensions Phase 2 (roadmap)

- Avenants et ordres de service
- Réceptions provisoire/définitive
- Finances complètes (factures, liquidation, mandats, paiements)
- Avances et garanties
- Notifications et relances automatiques
- Workflow multi-niveaux paramétrable
- GED avec versioning
- Contentieux et incidents
- Signature électronique
- Export Excel avancé

---

## Support

Pour toute question technique, contacter l'équipe de développement CANAM.
