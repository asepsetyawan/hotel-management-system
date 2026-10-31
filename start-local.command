#!/bin/bash
set -euo pipefail

# Run this file directly on macOS; it opens in Terminal.
export PATH="/opt/homebrew/bin:/usr/local/bin:$PATH"
PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$PROJECT_DIR"

on_error() {
  status=$?
  printf '\nSetup stopped (exit %s). See the error above.\n' "$status"
  if [ -t 0 ]; then read -r -p 'Press Return to close this window. ' _; fi
  exit "$status"
}
trap on_error ERR

printf '\nStay OS — local setup\nProject: %s\n\n' "$PROJECT_DIR"
for program in php composer; do
  if ! command -v "$program" >/dev/null 2>&1; then
    printf 'Missing prerequisite: %s. Install it before continuing.\n' "$program"
    exit 1
  fi
done
php -r 'if (version_compare(PHP_VERSION,"8.3.0","<")) {fwrite(STDERR,"Laravel 13 requires PHP 8.3 or newer.\n");exit(1);}'

# A project-local cache avoids the earlier unwritable Composer cache directory.
export COMPOSER_CACHE_DIR="$PROJECT_DIR/.runtime/composer-cache"
mkdir -p "$COMPOSER_CACHE_DIR"
if [ ! -f .env ]; then cp .env.example .env; fi

printf 'Installing dependencies…\n'
composer install --no-interaction --prefer-dist

# Never replace an existing application key or database.
if ! php -r '$s=file_get_contents(".env");exit(preg_match("/^APP_KEY=.+$/m",$s)?0:1);'; then
  php artisan key:generate --no-interaction
fi
DB_DRIVER=$(php -r 'require "vendor/autoload.php"; Dotenv\Dotenv::createImmutable(__DIR__)->load(); echo $_ENV["DB_CONNECTION"] ?? "pgsql";')
if [ "$DB_DRIVER" = "pgsql" ]; then
  php -r 'if (!extension_loaded("pdo_pgsql")) {fwrite(STDERR,"Enable PDO PostgreSQL before continuing.\n");exit(1);}'
  docker compose up -d --wait postgres
elif [ "$DB_DRIVER" = "sqlite" ]; then
  if [ ! -f database/database.sqlite ]; then touch database/database.sqlite; fi
fi
php artisan config:clear
php artisan migrate --seed --no-interaction
php artisan view:cache
php artisan route:list --except-vendor

printf '\nStarting Stay OS: http://127.0.0.1:8000/app\n'
printf 'Keep this terminal open. Press Ctrl+C to stop the server.\n'
printf 'For a new demo setup: admin@stayos.test / DEMO_ADMIN_PASSWORD from .env\n\n'
php artisan serve --host=127.0.0.1 --port=8000
