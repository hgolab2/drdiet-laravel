# syntax=docker/dockerfile:1

FROM php:8.2-fpm-alpine

# System deps
RUN apk add --no-cache \
    bash \
    curl \
    git \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    tzdata \
    $PHPIZE_DEPS

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

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first (better layer cache)
COPY composer.json composer.lock ./

# Install deps (no-dev by default; change with build arg)
ARG APP_ENV=production
RUN if [ "$APP_ENV" = "production" ]; then \
      composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader; \
    else \
      composer install --no-interaction --prefer-dist; \
    fi

# Copy application
COPY . .

# Permissions (Alpine: www-data exists)
RUN mkdir -p storage bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# OPcache recommended settings
RUN { \
  echo "opcache.enable=1"; \
  echo "opcache.enable_cli=0"; \
  echo "opcache.memory_consumption=128"; \
  echo "opcache.interned_strings_buffer=16"; \
  echo "opcache.max_accelerated_files=20000"; \
  echo "opcache.validate_timestamps=0"; \
  echo "opcache.revalidate_freq=0"; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
