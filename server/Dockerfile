# Stage 1: Build frontend assets
FROM node:20-alpine AS node-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 2: Production PHP environment
FROM serversideup/php:8.4-fpm-nginx

# Switch to root to install dependencies and set permissions
USER root

# Install system dependencies, Chromium, and Node (for Spatie Browsershot)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    chromium \
    nodejs \
    npm \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN install-php-extensions \
    bcmath \
    gd \
    intl \
    mbstring \
    pcntl \
    pdo_mysql \
    pdo_pgsql \
    pdo_sqlite \
    zip \
    opcache \
    exif \
    redis

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY --chown=www-data:www-data . .

# Copy built assets from stage 1
COPY --from=node-builder --chown=www-data:www-data /app/public/build ./public/build

# Install PHP dependencies as www-data
USER www-data
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Environment variables for Puppeteer (so it skips downloading its own chromium and uses the system one)
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium
RUN npm install puppeteer

# Switch back to root for final configuration
USER root

# Environment variables for the image
ENV AUTORUN_ENABLED=true
ENV AUTORUN_LARAVEL_MIGRATION=false
ENV PHP_OPCACHE_ENABLE=1
ENV APP_ENV=production
ENV APP_DEBUG=false

# Render uses the PORT environment variable
EXPOSE 8080

# The image automatically starts Nginx and PHP-FPM
