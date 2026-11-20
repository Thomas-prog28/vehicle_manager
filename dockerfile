FROM dunglas/frankenphp:1-php8.4-alpine

# Extensions PHP requises par Symfony + PostgreSQL + images
RUN install-php-extensions \
    pdo_pgsql \
    intl \
    zip \
    opcache \
    gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV APP_ENV=prod
ENV SERVER_NAME=:10000

# Installation des dépendances PHP sans les paquets de dev
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copie du code de l'application
COPY . .

# Compilation des assets (Tailwind CSS + Importmap) et cache Symfony
RUN php bin/console tailwind:build --minify \
    && php bin/console asset-map:compile \
    && php bin/console cache:warmup

# Script de démarrage (migrations BDD + lancement serveur)
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 10000

ENTRYPOINT ["docker-entrypoint.sh"]
