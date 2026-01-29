# syntax=docker/dockerfile:1
FROM php:8.2-fpm-bookworm

# --- system deps + nginx + supervisor ---
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl unzip \
    nginx supervisor \
    libicu-dev \
    libzip-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev \
    libonig-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# --- php extensions ---
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    zip \
    intl \
    gd \
    pcntl \
    opcache \
    dom \
    xml \
    simplexml \
    xmlreader \
    xmlwriter

# --- composer ---
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# copy composer files first
COPY composer.json composer.lock ./

ARG APP_ENV=production

RUN if [ "$APP_ENV" = "production" ]; then \
      composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts; \
    else \
      composer install --no-interaction --prefer-dist --no-scripts; \
    fi

# copy full app
COPY . .

# permissions
RUN mkdir -p storage bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# --- nginx config (inside container) ---
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# remove default site (safety)
RUN rm -f /etc/nginx/sites-enabled/default || true

# --- supervisor config ---
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# nginx listens on 8080 inside container (recommended to avoid root privileges for 80)
EXPOSE 8080

CMD ["/usr/bin/supervisord", "-n"]
