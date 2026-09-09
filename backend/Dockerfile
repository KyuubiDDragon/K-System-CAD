FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    default-mysql-client \
    wget \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Copy custom PHP configuration for uploads
COPY uploads.ini /usr/local/etc/php/conf.d/custom-uploads.ini


# Install Composer (pinned major version, :latest can break the build without notice)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Composer runs as root inside the image; silence the superuser warning
ENV COMPOSER_ALLOW_SUPERUSER=1

# Set working directory
WORKDIR /var/www/html

# Copy composer files and install dependencies first (for better layer caching).
# composer.lock is committed, so this installs a fixed set of packages instead of
# re-resolving the dependency graph against Packagist on every build.
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --prefer-dist \
        --optimize-autoloader \
    && composer clear-cache

# Copy existing application directory contents
# (.dockerignore keeps the local vendor/ out so the installed one is not overwritten)
COPY . /var/www/html

# Copy migrations directory
COPY migrations /var/www/html/migrations

# Copy and set permissions for entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Anlegen von Uploads-Verzeichnis und Berechtigungen
RUN mkdir -p /var/www/html/uploads && \
    mkdir -p /var/www/html/migrations/applied && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html/uploads && \
    chmod -R 755 /var/www/html/migrations

# Aktiviere Apache Rewrite und Headers Module
RUN a2enmod rewrite headers

# Copy and activate our custom VirtualHost configuration
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Create api directory and test files in both locations
RUN mkdir -p /var/www/html/api && \
    echo '<?php header("Content-Type: application/json"); echo json_encode(["location" => "api-dir", "status" => "ok", "timestamp" => time()]); ?>' > /var/www/html/api/test.php && \
    echo '<?php header("Content-Type: application/json"); echo json_encode(["location" => "root-dir", "status" => "ok", "timestamp" => time()]); ?>' > /var/www/html/test.php

# Erstelle einen einfachen Healthcheck-Endpunkt (direkt im Root-Verzeichnis)
RUN echo '<?php header("Content-Type: application/json"); echo json_encode(["status" => "ok", "timestamp" => time()]); ?>' > /var/www/html/health.php

# Healthcheck mit angepassten Werten
# Health check can be accessed via both /health.php and /api/health.php
HEALTHCHECK --interval=30s --timeout=15s --start-period=30s --retries=3 \
  CMD curl -f http://localhost/health.php || exit 1

# Use entrypoint script to run migrations before starting Apache
ENTRYPOINT ["docker-entrypoint.sh"] 