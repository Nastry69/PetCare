# ================================================================
# PetCare — Dockerfile PHP (Symfony 7.4 / PHP 8.4 FPM)
# ================================================================
FROM php:8.4-fpm-alpine

# ── Extensions PHP nécessaires ──────────────────────────────────
RUN apk add --no-cache \
        bash \
        git \
        unzip \
        openssl \
        libpq-dev \
        icu-dev \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        intl \
        opcache

# ── Config PHP (upload limits) ──────────────────────────────────
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# ── Composer ────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ── Répertoire de travail ────────────────────────────────────────
WORKDIR /app

# ── Copie du code (optimisé par layers) ─────────────────────────
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

COPY . .

RUN composer dump-autoload --optimize

# ── Permissions Symfony (var/) ───────────────────────────────────
RUN mkdir -p var config/jwt && chmod -R 777 var

# ── Entrypoint ────────────────────────────────────────────────────
COPY docker/php/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
