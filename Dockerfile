# syntax=docker/dockerfile:1
# =============================================================================
# OmniReply — single multi-stage FrankenPHP image.
# The SAME image runs every role (web / horizon / reverb / scheduler) by
# changing the start command. Two final targets: `dev` and `prod`.
# =============================================================================

# ---- base runtime: PHP extensions shared by every stage ---------------------
FROM dunglas/frankenphp:php8.4 AS base
WORKDIR /app
ENV COMPOSER_ALLOW_SUPERUSER=1
# Pin the latest extension installer (resilient redis/pecl sourcing).
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/install-php-extensions
# pdo_pgsql/pgsql -> Postgres(+pgvector via SQL); redis -> cache/queue/locks;
# pcntl -> Octane/Horizon; intl/opcache/zip/bcmath/gd -> framework + media.
RUN install-php-extensions \
      pdo_pgsql pgsql redis intl opcache pcntl zip bcmath gd
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ---- composer vendor (production deps only) ---------------------------------
FROM base AS vendor
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-scripts --no-autoloader --no-interaction

# ---- frontend assets --------------------------------------------------------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* vite.config.* ./
RUN npm ci
COPY resources resources
COPY public public
RUN npm run build

# ---- dev target: full deps, code bind-mounted at runtime --------------------
FROM base AS dev
ENV APP_ENV=local
# Node + chokidar make `--watch` available (opt-in via `make watch`).
RUN apt-get update \
 && apt-get install -y --no-install-recommends nodejs npm git \
 && rm -rf /var/lib/apt/lists/*
COPY composer.json composer.lock ./
RUN composer install --prefer-dist --no-scripts --no-autoloader --no-interaction
COPY . .
RUN composer dump-autoload
EXPOSE 8000 8080
# Dev web server is `php artisan serve` (NOT Octane — Octane is the prod app
# server). For the full host dev experience use `composer dev`.
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

# ---- prod target: optimized, self-contained ---------------------------------
FROM base AS prod
ENV APP_ENV=production
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
# NOTE: do NOT `php artisan optimize` (config:cache) at build time — there is no
# .env here, so it would bake default config and the runtime container would
# ignore its injected env. Cache config at container start instead, once env is
# present (e.g. an entrypoint running `php artisan config:cache`).
RUN composer dump-autoload --optimize --classmap-authoritative \
 && chown -R www-data:www-data storage bootstrap/cache
EXPOSE 8000
CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=8000"]
