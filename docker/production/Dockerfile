FROM php:8.4-cli-alpine AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
      --no-dev \
      --no-scripts \
      --no-interaction \
      --prefer-dist \
      --no-autoloader \
      --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts

FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

FROM php:8.4-fpm-alpine AS app
WORKDIR /var/www/html

RUN apk add --no-cache \
      postgresql-dev \
      postgresql17-client \
      libzip-dev \
      icu-dev \
      libpng-dev \
      libwebp-dev \
      freetype-dev \
      $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-webp \
    && docker-php-ext-install -j1 \
      pdo_pgsql \
      pgsql \
      zip \
      intl \
      gd \
      exif \
      bcmath \
      opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/production/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/production/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY docker/production/php/www.conf /usr/local/etc/php-fpm.d/zz-www.conf

RUN addgroup -g 1000 banda && adduser -G banda -u 1000 -D banda
COPY --chown=banda:banda --from=vendor /app/vendor ./vendor
COPY --chown=banda:banda --from=assets /app/public/build ./public/build
COPY --chown=banda:banda . .

RUN chmod -R 775 storage bootstrap/cache

USER banda

EXPOSE 9000
CMD ["php-fpm"]

FROM nginx:alpine AS web
COPY --from=assets /app/public /var/www/html/public
COPY docker/production/nginx.conf.template /etc/nginx/templates/default.conf.template
