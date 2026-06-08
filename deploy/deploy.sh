#!/usr/bin/env bash
#
# deploy.sh — run on every release, from the repository root on the server.
#   bash deploy/deploy.sh
#
# Pulls latest code, installs deps, builds assets, migrates, and caches config.
# Idempotent and safe to re-run.
#
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "${ROOT_DIR}/backend"

echo "==> Pulling latest code"
git -C "${ROOT_DIR}" pull --ff-only || echo "(skipping git pull)"

echo "==> Composer (production)"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo "==> Front-end assets"
npm ci
npm run build

echo "==> Maintenance window"
php artisan down --render="errors::503" || true

echo "==> Migrations"
php artisan migrate --force

echo "==> Storage symlink"
php artisan storage:link || true

echo "==> Cache config / routes / views / events"
php artisan optimize

echo "==> Restart workers"
php artisan queue:restart

echo "==> Up"
php artisan up

echo
echo "Deploy complete. If services need reloading:"
echo "  sudo systemctl reload php8.4-fpm"
echo "  sudo supervisorctl restart complygcc-worker:*"
