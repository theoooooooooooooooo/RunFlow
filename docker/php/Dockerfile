# Image de base : PHP 8.4 en mode CLI (utilisée pour le dev, via docker-entrypoint / symfony server)
FROM php:8.4-cli

# Installe les paquets système nécessaires à Composer, Git et à la compilation des extensions PHP
RUN apt update && apt install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    zip \
    && rm -rf /var/lib/apt/lists/*

# Installe les extensions PHP requises par l'appli : accès PDO/MySQL et cache opcode
RUN docker-php-ext-install pdo pdo_mysql opcache

# Configuration OPcache (gain de performance le plus important)
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
# Configuration PHP custom (php.ini) appliquée après la conf par défaut (préfixe zz- = chargée en dernier)
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-custom.ini

# Récupère le binaire Composer depuis l'image officielle composer, sans installer Composer soi-même
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Installe la CLI Symfony (utilitaire de dev : serveur local, "symfony console", etc.)
# Le script d'installation est recupere via ADD (recommande par Docker pour les ressources
# distantes plutot qu'un curl dans un RUN), verifie non vide, puis execute et nettoye
# en une seule instruction RUN pour limiter le nombre de couches de l'image
ADD https://get.symfony.com/cli/installer /tmp/symfony-installer.sh
RUN test -s /tmp/symfony-installer.sh \
    && bash /tmp/symfony-installer.sh \
    && rm /tmp/symfony-installer.sh \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

# Répertoire de travail dans le conteneur : le code de l'appli y est monté en volume (voir docker-compose.yml)
WORKDIR /var/www/html