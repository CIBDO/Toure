#!/usr/bin/env bash
# Script de déploiement CANAM — à exécuter sur le serveur après chaque git pull
# Usage: cd /var/www/canam && chmod +x deploy.sh && ./deploy.sh

set -e
echo "=== Déploiement CANAM ==="

# Répertoire du projet (où se trouve ce script)
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

# 1. Récupérer les dernières modifications (si dépôt Git)
if [ -d .git ]; then
  echo ">>> git pull..."
  git pull
else
  echo ">>> Pas de dépôt Git — poursuite avec les fichiers actuels."
fi

# 2. Dépendances PHP
echo ">>> composer install --no-dev..."
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Migrations
echo ">>> php artisan migrate --force..."
php artisan migrate --force

# 4. Lien storage (si pas déjà fait)
if [ ! -L public/storage ]; then
  echo ">>> php artisan storage:link..."
  php artisan storage:link
fi

# 5. Caches
echo ">>> Caches (config, route, view)..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Permissions (adapter www-data si votre serveur utilise un autre utilisateur)
echo ">>> Permissions storage & bootstrap/cache..."
if [ -n "$SUDO_USER" ]; then
  sudo chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
fi
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo "=== Déploiement terminé ==="
echo "Pensez à refaire un build frontend (npm run build) si vous avez modifié le front, puis à re-déployer les fichiers public/build."
