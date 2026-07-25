# syntax=docker/dockerfile:1

# FrankenPHP bundles PHP 8.4 and a Caddy web server in one process, which suits
# a single-container host like Render better than an nginx + php-fpm pair.
FROM dunglas/frankenphp:1-php8.4 AS base

RUN install-php-extensions pdo_pgsql pdo_mysql intl zip bcmath opcache

WORKDIR /app


# --- build -------------------------------------------------------------------
# Composer and Node both run here: Wayfinder generates its TypeScript route
# helpers by booting Artisan during `vite build`, so PHP has to be on the path
# while the front end compiles.
FROM base AS build

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl ca-certificates \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# A throwaway key so Artisan can boot during the build. The real one arrives as
# an environment variable at run time.
ENV APP_KEY=base64:Zm9yLWJ1aWxkLXRpbWUtYXJ0aXNhbi1vbmx5LS0tLS0tLS09
ENV APP_ENV=production

# Dev dependencies stay in the image on purpose: the demo deployment seeds
# itself on first boot, and the seeders run through model factories, which need
# Faker. Promoting Faker to a production requirement would be worse.
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative \
    && php artisan package:discover --ansi \
    && npm run build \
    && rm -rf node_modules


# --- runtime -----------------------------------------------------------------
FROM base AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

COPY --from=build /app /app
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

RUN chmod +x /usr/local/bin/entrypoint \
    && chmod -R ug+w storage bootstrap/cache

ENTRYPOINT ["entrypoint"]
