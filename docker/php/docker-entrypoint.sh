#!/bin/sh
set -e

# Symfony exige un fichier .env même si les variables sont deja injectees par Docker
touch /var/www/html/.env

echo "Nettoyage et prechauffage du cache Symfony..."
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug

echo "Execution des migrations Doctrine..."
php bin/console doctrine:migrations:migrate --env=prod --no-interaction

exec "$@"