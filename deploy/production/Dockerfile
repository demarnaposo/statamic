# syntax=docker/dockerfile:1
# Production images for the platform (Laravel + Statamic). Build context = repository root.
#
#   target `app`: PHP-FPM with the code baked in. One image, several roles chosen by the command:
#                           php-fpm (app / admin), queue:work, schedule:work, migrate.
#   target `web`:           nginx with public/ (static files) that forwards PHP to the FPM upstreams.
#
#   docker build -f deploy/production/Dockerfile --target app -t sertifika/platform:TAG .
#   docker build -f deploy/production/Dockerfile --target web -t sertifika/platform-web:TAG .

# ---- PHP runtime: PHP extensions for Statamic + redis ----
FROM docker.io/library/php:8.4-fpm-alpine AS php-base
COPY --from=docker.io/mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql intl gd exif zip bcmath pcntl opcache redis \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY deploy/production/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY deploy/production/php/fpm-pool.conf /usr/local/etc/php-fpm.d/zz-app.conf
WORKDIR /srv/app

# ---- Composer dependencies (no dev) + Statamic CP assets published into public/vendor ----
FROM php-base AS vendor
COPY --from=docker.io/library/composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_INTERACTION=1
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress
COPY . .
# post-autoload-dump runs package:discover + statamic:install (publishes public/vendor/statamic).
# Throwaway settings: nothing here may touch a real database or cache.
RUN mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && APP_ENV=production APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
       DB_CONNECTION=sqlite DB_DATABASE=:memory: CACHE_STORE=array SESSION_DRIVER=array QUEUE_CONNECTION=sync \
       composer dump-autoload --no-dev --optimize \
    && test -f public/vendor/statamic/cp/build/manifest.json \
    && rm -rf bootstrap/cache/*.php storage/framework/views/* /root/.composer

# ---- Vite assets (portal + site) ----
FROM docker.io/library/node:24-alpine AS assets
WORKDIR /srv/app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js tsconfig.json ./
COPY resources resources
COPY modules modules
COPY content content
RUN npm run build

# ---- target: app (PHP-FPM, non-root, code read-only for the runtime user) ----
FROM php-base AS app
COPY --from=vendor /srv/app /srv/app
COPY --from=assets /srv/app/public/build /srv/app/public/build
COPY deploy/production/php/entrypoint.sh /usr/local/bin/platform-entrypoint
# Writable at runtime: storage/ (volume), bootstrap/cache (tmpfs), public/assets (volume, Statamic uploads).
RUN chmod 755 /usr/local/bin/platform-entrypoint \
    && mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions \
       storage/framework/views storage/logs storage/statamic bootstrap/cache public/assets \
    && ln -sfn ../storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache public/assets
# Root filesystem is read-only in compose: compiled views and tinker (psysh) config live in /tmp (tmpfs).
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr VIEW_COMPILED_PATH=/tmp/views XDG_CONFIG_HOME=/tmp/.config
USER www-data
EXPOSE 9000
ENTRYPOINT ["platform-entrypoint"]
CMD ["php-fpm"]

# ---- target: web (nginx-unprivileged + public/) ----
FROM docker.io/nginxinc/nginx-unprivileged:1.29-alpine AS web
# Only these variables are substituted in the template; nginx's own $host, $uri, ... stay untouched.
ENV NGINX_ENVSUBST_FILTER="^(APP_DOMAIN|ADMIN_DOMAIN|APP_FPM|ADMIN_FPM|NGINX_LOCAL_RESOLVERS)$" \
    NGINX_ENTRYPOINT_LOCAL_RESOLVERS=1 APP_FPM=app:9000 ADMIN_FPM=admin:9000
COPY deploy/production/nginx/platform.conf.template /etc/nginx/templates/platform.conf.template
COPY deploy/production/nginx/platform-common.inc deploy/production/nginx/platform-fastcgi.inc /etc/nginx/snippets/
COPY --from=app /srv/app/public /srv/app/public
# public/assets keeps www-data ownership in case this image is the first to populate the shared volume.
USER root
RUN rm -f /etc/nginx/conf.d/default.conf && chown 82:82 /srv/app/public/assets
USER 101
EXPOSE 8080
