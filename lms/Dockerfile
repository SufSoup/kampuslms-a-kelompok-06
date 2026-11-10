# Stage 1: Build frontend assets
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: PHP Application Environment
FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Configure PHP upload limits (modul.md §12.1: upload_max_filesize & post_max_size)
RUN echo "upload_max_filesize = 20M\npost_max_size = 25M\nmemory_limit = 256M" > /usr/local/etc/php/conf.d/uploads.ini

# Configure Apache MPM (disable event/worker, enable prefork to avoid AH00534)
RUN a2dismod mpm_event mpm_worker || true \
    && a2enmod mpm_prefork

# Enable Apache rewrite module
RUN a2enmod rewrite

# Configure Apache DocumentRoot to Laravel's public directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

# Copy Composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application source code
COPY . .

# Copy compiled frontend assets from Stage 1
COPY --from=frontend /app/public/build ./public/build

# Install PHP production dependencies without dev packages
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions for storage and cache directories
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Expose HTTP and Railway default ports
EXPOSE 80 8080

# Production startup: bind dynamic $PORT, run migrations, compile cache, start Apache
CMD sh -c "\
    PORT=\${PORT:-8080}; \
    sed -i \"s/Listen 80/Listen \$PORT/g\" /etc/apache2/ports.conf; \
    sed -i \"s/<VirtualHost \*:80>/<VirtualHost *:\$PORT>/g\" /etc/apache2/sites-available/000-default.conf; \
    php artisan migrate --force && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan event:cache && \
    apache2-foreground"
