#!/bin/sh
set -e

echo "Nettoyage et prechauffage du cache Symfony..."
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug

echo "Execution des migrations Doctrine..."
php bin/console doctrine:migrations:migrate --env=prod --no-interaction

exec "$@"