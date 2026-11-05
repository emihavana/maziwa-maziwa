#!/usr/bin/env bash
set -e
cd /var/www/muhoko

git fetch origin
git reset --hard origin/main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear
