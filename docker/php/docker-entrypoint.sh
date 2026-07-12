#!/bin/sh
set -e

# Symfony exige un fichier .env meme si les variables sont deja injectees par Docker
touch /var/www/html/.env

echo "Attente de la disponibilite de la base de donnees..."
until php bin/console dbal:run-sql "SELECT 1" --env=prod > /dev/null 2>&1; do
    echo "Base de donnees non disponible, nouvelle tentative dans 2s..."
    sleep 2
done
echo "Base de donnees disponible !"

echo "Nettoyage et prechauffage du cache Symfony..."
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug

echo "Execution des migrations Doctrine..."
php bin/console doctrine:migrations:migrate --env=prod --no-interaction

exec "$@"