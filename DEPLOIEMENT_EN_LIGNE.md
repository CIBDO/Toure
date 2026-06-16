# Déploiement en ligne — CANAM

Guide pas à pas pour mettre l’application CANAM en production sur un serveur (VPS ou hébergement mutualisé).

---

## Comment déployer le frontend ? (build)

Le dossier **`public/build`** (résultat de `npm run build`) est **ignoré par Git** — il ne sera jamais sur GitHub. Vous avez donc **deux façons** de l’avoir sur le serveur :

| Méthode | Quand l’utiliser |
|--------|-------------------|
| **A. Builder sur le serveur** | Recommandé : vous poussez uniquement le code sur GitHub, vous clonez sur le serveur, puis vous exécutez `npm run build` **sur le serveur** (avec Node.js installé). Pas besoin de builder en local pour le déploiement. |
| **B. Builder en local puis envoyer** | Vous builderez en local avec l’URL de prod, puis vous enverrez **uniquement** le dossier `public/build` sur le serveur (SFTP, rsync, etc.). Le reste du code vient du clone Git. |

En résumé : **vous n’avez pas besoin de builder en local avant de pousser sur GitHub.** Vous pouvez pousser le code, cloner sur le serveur, puis builder sur le serveur (méthode A).

---

## Vue d’ensemble

| Étape | Où | Quoi |
|-------|-----|-----|
| 1 | GitHub | Push du code (sans `public/build`) |
| 2 | Serveur | Clone du dépôt, installation Nginx + PHP + MySQL (+ Node.js si build sur le serveur) |
| 3 | Serveur | Build frontend (sur le serveur **ou** upload de `public/build` fait en local) |
| 4 | Serveur | `.env` production, migrations, caches, `deploy.sh` |

**Option A :** Vous avez un VPS (Ubuntu/Debian) avec accès SSH → suivez **Partie I**.  
**Option B :** Hébergement mutualisé (cPanel, etc.) → suivez **Partie II** (résumé).

---

# Partie I — Déploiement sur VPS (Ubuntu / Debian)

## Prérequis serveur

- Ubuntu 22.04 LTS ou Debian 12
- Accès root ou sudo
- Un nom de domaine pointant vers l’IP du serveur (ex. `canam.votredomaine.ml`)

---

## Étape 1 — Build du frontend sur le serveur (recommandé)

Ainsi vous n’avez rien à builder en local ni à envoyer à part le code via Git.

1. Installer Node.js 18+ sur le serveur (une fois) :
   ```bash
   curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
   sudo apt install -y nodejs
   ```

2. Dans le projet (après clone), définir l’URL de prod et builder :
   ```bash
   cd /var/www/canam
   export VITE_API_BASE_URL="https://canam.votredomaine.ml/api"
   npm ci
   npm run build
   ```
   Vérifier que `public/build` contient des fichiers.

À chaque mise à jour (après `git pull`), si vous avez modifié le frontend, refaire :
   ```bash
   export VITE_API_BASE_URL="https://canam.votredomaine.ml/api"
   npm ci
   npm run build
   ```
   (Vous pouvez aussi ajouter ces lignes dans `deploy.sh` pour tout automatiser.)

---

## Étape 1 bis — Build en local puis envoi (optionnel)

Si vous ne voulez pas installer Node sur le serveur : builder sur votre PC puis envoyer **uniquement** le dossier `public/build` sur le serveur (SFTP, etc.). Le reste vient du clone Git.

1. Ouvrir PowerShell dans le dossier du projet :
   ```powershell
   cd C:\Users\BDO\Desktop\CANAM
   ```

2. Définir l’URL de production (remplacer par votre vraie URL) :
   ```powershell
   $env:VITE_API_BASE_URL = "https://canam.votredomaine.ml/api"
   ```

3. Installer les dépendances et builder :
   ```powershell
   npm ci
   npm run build
   ```

4. Vérifier que le dossier `public\build` contient des fichiers. Ensuite, envoyer **uniquement** ce dossier `public/build` sur le serveur (SFTP, WinSCP, rsync, etc.) dans `/var/www/canam/public/build` après avoir cloné le dépôt.

---

## Étape 2 — Installer PHP, MySQL, Nginx sur le serveur

En SSH sur le serveur :

```bash
sudo apt update
sudo apt install -y php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd
sudo apt install -y nginx mysql-server
sudo systemctl enable nginx php8.2-fpm mysql
```

Sécuriser MySQL (mot de passe root, etc.) :

```bash
sudo mysql_secure_installation
```

Créer la base et l’utilisateur pour CANAM :

```bash
sudo mysql -e "
CREATE DATABASE canam_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'canam_user'@'localhost' IDENTIFIED BY 'VOTRE_MOT_DE_PASSE_FORT';
GRANT ALL ON canam_db.* TO 'canam_user'@'localhost';
FLUSH PRIVILEGES;
"
```

---

## Étape 3 — Cloner le projet et configurer le serveur web

1. Installer Git si besoin :
   ```bash
   sudo apt install -y git
   ```

2. Cloner le dépôt (remplacer par votre URL Git) :
   ```bash
   sudo mkdir -p /var/www
   sudo chown $USER:$USER /var/www
   cd /var/www
   git clone https://github.com/VOTRE_ORG/canam.git
   cd canam
   ```

   Si vous n’utilisez pas Git : uploader les fichiers (y compris `public/build`) par SFTP dans `/var/www/canam`.

3. Copier l’exemple d’environnement et éditer le `.env` :
   ```bash
   cp .env.example .env
   nano .env
   ```

   Renseigner au minimum :
   - `APP_NAME="CANAM Contract Manager"`
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://canam.votredomaine.ml` (obligatoire en prod : utilisé pour les liens dans les emails, ex. réinitialisation mot de passe)
   - `FRONTEND_URL` : optionnel. Si non défini, les emails utilisent `APP_URL`. À mettre uniquement si le front est sur un autre domaine.
   - `APP_KEY=` → sera rempli à l’étape 4
   - `DB_CONNECTION=mysql`
   - `DB_DATABASE=canam_db`
   - `DB_USERNAME=canam_user`
   - `DB_PASSWORD=VOTRE_MOT_DE_PASSE_FORT`
   - `LOG_LEVEL=error`
   - `SESSION_SECURE_COOKIE=true`
   - `VITE_API_BASE_URL=https://canam.votredomaine.ml/api` (pour cohérence ; le build l’a déjà utilisé)

4. Créer le fichier de configuration Nginx (vous pouvez copier `deploy/nginx-canam.conf.example` sur le serveur) :

   ```bash
   sudo cp /var/www/canam/deploy/nginx-canam.conf.example /etc/nginx/sites-available/canam
   sudo nano /etc/nginx/sites-available/canam
   ```

   Remplacer `canam.votredomaine.ml` par votre domaine et `/var/www/canam` par votre chemin si besoin. Exemple de contenu :

   ```nginx
   server {
       listen 80;
       listen [::]:80;
       server_name canam.votredomaine.ml;
       root /var/www/canam/public;

       add_header X-Frame-Options "SAMEORIGIN";
       add_header X-Content-Type-Options "nosniff";
       index index.php;

       charset utf-8;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location = /favicon.ico { access_log off; log_not_found off; }
       location = /robots.txt  { access_log off; log_not_found off; }

       error_page 404 /index.php;

       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
           include fastcgi_params;
           fastcgi_hide_header X-Powered-By;
           fastcgi_read_timeout 300;
           fastcgi_buffers 16 16k;
           fastcgi_buffer_size 32k;
       }

       location ~ /\.(?!well-known).* {
           deny all;
       }
   }
   ```

   Activer le site et recharger Nginx :
   ```bash
   sudo ln -s /etc/nginx/sites-available/canam /etc/nginx/sites-enabled/
   sudo nginx -t
   sudo systemctl reload nginx
   ```

---

## Étape 4 — Premier déploiement Laravel

Toujours dans `/var/www/canam` :

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Donner les droits d’écriture à PHP (utilisateur de PHP-FPM, souvent `www-data`) :

```bash
sudo chown -R www-data:www-data /var/www/canam/storage /var/www/canam/bootstrap/cache
sudo chmod -R 775 /var/www/canam/storage /var/www/canam/bootstrap/cache
```

Créer l’utilisateur admin (une seule fois) :

```bash
php artisan tinker
```

Dans Tinker :

```php
$admin = App\Models\User::create([
    'name'         => 'Admin CANAM',
    'nom'          => 'Admin',
    'prenom'       => 'CANAM',
    'email'        => 'admin@canam.ml',
    'password'     => bcrypt('VOTRE_MOT_DE_PASSE_ADMIN'),
    'statut'       => 'ACTIF',
    'type_compte'  => 'SYSTEME',
]);
$admin->roles()->attach(App\Models\Role::where('code', 'ADMIN_DM')->first());
exit
```

---

## Étape 5 — HTTPS (Let’s Encrypt)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d canam.votredomaine.ml
```

Suivre les instructions. Après ça, le site sera en `https://canam.votredomaine.ml`.

---

## Étape 6 — Mises à jour (déploiement continu)

À chaque nouvelle version :

1. Sur le serveur :
   ```bash
   cd /var/www/canam
   ./deploy.sh
   ```

   Le script **`deploy.sh`** à la racine du projet fait : `git pull`, `composer install --no-dev`, `php artisan migrate --force`, caches, etc. Rendez-le exécutable une fois : `chmod +x deploy.sh`.

2. Si vous avez changé le frontend : refaire un build sur votre PC avec `VITE_API_BASE_URL` puis pousser les fichiers (ou builder sur le serveur si Node est installé) avant d’exécuter `deploy.sh`.

---

# Partie II — Hébergement mutualisé (type cPanel)

- **PHP :** Version 8.2 ou supérieure, extensions : mbstring, xml, curl, zip, bcmath, gd, pdo_mysql.
- **Base de données :** Créer une base MySQL et un utilisateur via cPanel (MySQL® Databases).
- **Fichiers :** Uploader tout le projet (y compris `public/build` après `npm run build` en local) dans le répertoire prévu (souvent `public_html` ou un sous-dossier). Le **Document Root** doit pointer vers le dossier **`public`** du projet (pas la racine du projet).
- **.env :** Créer un fichier `.env` à la racine du projet (à côté de `composer.json`) à partir de `.env.example`, avec les infos de base fournies par l’hébergeur.
- **Ligne de commande :** Si disponible (SSH ou “Terminal” cPanel), exécuter :
  ```bash
  composer install --no-dev --optimize-autoloader
  php artisan key:generate
  php artisan migrate --force
  php artisan db:seed
  php artisan storage:link
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```
- **Permissions :** `storage` et `bootstrap/cache` en écriture (souvent 755 ou 775 selon l’hébergeur).
- **SSL :** Activer le certificat SSL fourni par l’hébergeur et mettre `SESSION_SECURE_COOKIE=true` dans `.env`.

---

# Résumé des commandes utiles

| Action | Commande |
|--------|----------|
| Build frontend (Windows, avant déploiement) | `$env:VITE_API_BASE_URL="https://..."; npm ci; npm run build` |
| Déploiement sur le serveur (après pull) | `./deploy.sh` |
| Vider les caches | `php artisan config:clear && php artisan route:clear && php artisan view:clear` |
| Lien « réinitialiser mot de passe » dans l’email pointe vers localhost | Vérifier que `.env` contient `APP_URL=https://votredomaine.ml` (sans trailing slash). Puis `php artisan config:clear` et éventuellement `php artisan config:cache` |
| Mode maintenance | `php artisan down` / `php artisan up` |
| Voir les logs | `tail -f storage/logs/laravel.log` |
| Voir les logs | `tail -f storage/logs/laravel.log` |

---

# Fichiers fournis dans le projet

- **`DEPLOIEMENT_EN_LIGNE.md`** (ce fichier) — Guide déploiement.
- **`deploy.sh`** (à la racine) — Script à exécuter sur le serveur à chaque déploiement (`chmod +x deploy.sh` puis `./deploy.sh`).
- **`deploy/build-on-windows.ps1`** — Script PowerShell pour builder le frontend avec l’URL de prod : `.\deploy\build-on-windows.ps1 -ApiBaseUrl "https://votredomaine.ml/api"`.
- **`deploy/nginx-canam.conf.example`** — Exemple de configuration Nginx à adapter et placer dans `/etc/nginx/sites-available/`.
- **`.env.example`** — Modèle de variables d’environnement.

En cas de problème, vérifier les logs Laravel (`storage/logs/laravel.log`) et les erreurs Nginx (`sudo tail -f /var/log/nginx/error.log`).
