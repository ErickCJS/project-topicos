#!/usr/bin/env bash
set -Eeuo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
APP_DIR="${CPANEL_APP_DIR:-$HOME/apps/project-topicos}"

if [[ ! -f "$REPO_DIR/artisan" || ! -f "$REPO_DIR/composer.json" ]]; then
  echo "ERROR: No se reconoce la raíz del proyecto Laravel: $REPO_DIR"
  exit 1
fi

mkdir -p "$APP_DIR"
if [[ ! -f "$APP_DIR/.env" ]]; then
  echo "ERROR: Falta $APP_DIR/.env. Créalo con los datos de producción antes del despliegue."
  exit 1
fi

if ! command -v rsync >/dev/null 2>&1; then
  echo "ERROR: Este hosting debe tener rsync disponible."
  exit 1
fi

rsync -a --delete \
  --exclude='.git/' \
  --exclude='.env' \
  --exclude='vendor/' \
  --exclude='node_modules/' \
  --exclude='storage/app/' \
  --exclude='storage/framework/cache/' \
  --exclude='storage/framework/sessions/' \
  --exclude='storage/framework/views/' \
  --exclude='storage/logs/' \
  --exclude='public/storage' \
  "$REPO_DIR/" "$APP_DIR/"

cd "$APP_DIR"
mkdir -p storage/app/private storage/app/public storage/framework/{cache,sessions,views} storage/logs bootstrap/cache

composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

if grep -Eq '^APP_KEY=$' .env; then
  php artisan key:generate --force
fi

if [[ -f package.json ]]; then
  if ! command -v npm >/dev/null 2>&1; then
    echo "ERROR: Node.js/npm es necesario para compilar los assets de Tailwind."
    exit 1
  fi
  npm ci
  npm run build
fi

chmod -R ug+rwx storage bootstrap/cache

php artisan storage:link || true
php artisan migrate --force
php artisan config:cache
php artisan view:cache

echo "Despliegue de project-topicos finalizado."
