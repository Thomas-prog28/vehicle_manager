FROM dunglas/frankenphp:1-php8.4-bookworm

# Extensions PHP requises par Symfony + PostgreSQL + traitement d'images
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

# Variables factices uniquement pour compiler les assets et le cache
RUN APP_SECRET=build_secret \
    DATABASE_URL="postgresql://user:pass@localhost:5432/db?serverVersion=16&charset=utf8" \
    GEMINI_API_KEY=build_key \
    MAILER_DSN=null://null \
    MESSENGER_TRANSPORT_DSN=doctrine://default \
    php bin/console importmap:install

RUN APP_SECRET=build_secret \
    DATABASE_URL="postgresql://user:pass@localhost:5432/db?serverVersion=16&charset=utf8" \
    GEMINI_API_KEY=build_key \
    MAILER_DSN=null://null \
    MESSENGER_TRANSPORT_DSN=doctrine://default \
    php bin/console tailwind:build --minify

RUN APP_SECRET=build_secret \
    DATABASE_URL="postgresql://user:pass@localhost:5432/db?serverVersion=16&charset=utf8" \
    GEMINI_API_KEY=build_key \
    MAILER_DSN=null://null \
    MESSENGER_TRANSPORT_DSN=doctrine://default \
    php bin/console asset-map:compile

RUN APP_SECRET=build_secret \
    DATABASE_URL="postgresql://user:pass@localhost:5432/db?serverVersion=16&charset=utf8" \
    GEMINI_API_KEY=build_key \
    MAILER_DSN=null://null \
    MESSENGER_TRANSPORT_DSN=doctrine://default \
    php bin/console cache:warmup

# Script de démarrage (migrations BDD + lancement serveur)
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 10000

ENTRYPOINT ["docker-entrypoint.sh"]
