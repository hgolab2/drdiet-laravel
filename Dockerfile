# syntax=docker/dockerfile:1
FROM php:8.2-fpm-bookworm

# System deps
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl unzip \
    libicu-dev \
    libzip-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    zip \
    intl \
    gd \
    pcntl \
    opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first (cache-friendly)
COPY composer.json composer.lock ./

ARG APP_ENV=production

# CI-friendly: no scripts during build (artisan needs env sometimes)
RUN if [ "$APP_ENV" = "production" ]; then \
      composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts; \
    else \
      composer install --no-interaction --prefer-dist --no-scripts; \
    fi

# Copy app
COPY . .

# Permissions
RUN mkdir -p storage bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

USER www-data
EXPOSE 9000
CMD ["php-fpm"]
