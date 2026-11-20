#!/bin/sh
set -e

mkdir -p var/cache var/log public/uploads/invoices public/uploads/insurances
chmod -R 775 var public/uploads

# Exécute automatiquement les migrations sur ta base Neon à chaque déploiement
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Démarre FrankenPHP sur le port 10000 (port standard de Render)
exec frankenphp php-server --listen :10000 --root /app/public
