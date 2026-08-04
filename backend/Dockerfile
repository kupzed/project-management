# ============================================
# Backend Dockerfile — Laravel 13 + PHP 8.4-FPM
# ============================================
#
# Penjelasan:
# Dockerfile ini menggunakan "multi-stage build", artinya proses build
# dibagi menjadi beberapa tahap. Tahap pertama install dependencies,
# tahap kedua membuat image final yang lebih ringan.
#
# Image final menggunakan PHP-FPM (FastCGI Process Manager), yaitu
# cara standar menjalankan PHP di production. FPM menerima request
# dari Nginx via protokol FastCGI (bukan HTTP biasa).

# -----------------------------------------------
# Stage 1: Install PHP dependencies via Composer
# -----------------------------------------------
FROM composer:2 AS composer-deps

WORKDIR /app

# Copy file dependency terlebih dahulu (untuk Docker layer caching)
# Jika composer.json/lock tidak berubah, Docker akan gunakan cache
COPY composer.json composer.lock ./

# Install dependencies tanpa dev packages (lebih ringan untuk production)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

# Copy seluruh source code, lalu generate autoloader
COPY . .

RUN composer dump-autoload --optimize --no-dev

# -----------------------------------------------
# Stage 2: Production image dengan PHP 8.4-FPM
# -----------------------------------------------
FROM php:8.4-fpm-alpine

# Metadata image
LABEL maintainer="kupzed"
LABEL description="Indogreen Project Management — Laravel Backend"

# Install PHP extensions yang dibutuhkan Laravel
# - pdo_mysql: koneksi ke MySQL
# - bcmath: operasi matematika presisi tinggi
# - opcache: cache bytecode PHP (performa 2-3x lebih cepat)
# - pcntl: process control (untuk queue worker)
RUN apk add --no-cache \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        oniguruma-dev \
        icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        bcmath \
        opcache \
        pcntl \
        gd \
        mbstring \
        intl \
    && rm -rf /var/cache/apk/*

# Konfigurasi OPcache untuk production
RUN echo 'opcache.enable=1' >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo 'opcache.memory_consumption=128' >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo 'opcache.interned_strings_buffer=8' >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo 'opcache.max_accelerated_files=10000' >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo 'opcache.validate_timestamps=0' >> /usr/local/etc/php/conf.d/opcache.ini

# Konfigurasi PHP untuk production
RUN echo 'upload_max_filesize=20M' >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo 'post_max_size=25M' >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo 'memory_limit=256M' >> /usr/local/etc/php/conf.d/uploads.ini

# Set working directory
WORKDIR /var/www/backend

# Copy source code + vendor dari stage 1
COPY --from=composer-deps /app /var/www/backend

# Buat direktori yang dibutuhkan Laravel dan set permissions
# www-data adalah user default PHP-FPM (non-root, lebih aman)
RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        storage/app/public \
        bootstrap/cache \
    && chown -R www-data:www-data \
        storage \
        bootstrap/cache \
    && chmod -R 775 \
        storage \
        bootstrap/cache

# Jalankan sebagai user non-root (best practice keamanan)
USER www-data

# PHP-FPM default listen di port 9000
EXPOSE 9000

# Command default: jalankan PHP-FPM
CMD ["php-fpm"]
