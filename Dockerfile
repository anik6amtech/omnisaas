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
# Plain (no --watch) for a guaranteed-clean boot; `make watch` enables hot reload.
CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=8000"]

# ---- prod target: optimized, self-contained ---------------------------------
FROM base AS prod
ENV APP_ENV=production
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
RUN composer dump-autoload --optimize \
 && php artisan optimize \
 && chown -R www-data:www-data storage bootstrap/cache
EXPOSE 8000
CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=8000"]
