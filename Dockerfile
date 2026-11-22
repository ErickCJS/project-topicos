# PHP base with Laravel-required extensions
FROM php:8.3-apache AS php-base
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libonig-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring bcmath exif gd zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Install PHP dependencies
FROM php-base AS composer-deps
WORKDIR /var/www/html
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY lang ./lang
COPY public ./public
COPY resources ./resources
COPY routes ./routes
COPY storage ./storage
COPY artisan ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Build Tailwind assets, including vendor Blade templates
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY tailwind.config.js postcss.config.js ./
COPY --from=composer-deps /var/www/html/vendor ./vendor
RUN npm run build

# Final Apache runtime: explicitly configure Laravel public directory
FROM php-base
RUN cat > /etc/apache2/sites-available/000-default.conf <<'APACHE'
<VirtualHost *:10000>
    ServerName localhost
    DocumentRoot /var/www/html/public
    DirectoryIndex index.php
    <Directory /var/www/html/public>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
APACHE

RUN sed -ri 's/^Listen 80$/Listen 10000/' /etc/apache2/ports.conf

WORKDIR /var/www/html
COPY --from=composer-deps /var/www/html ./
COPY --from=assets /app/public/css/app.css ./public/css/app.css

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/laravel-entrypoint
RUN chmod +x /usr/local/bin/laravel-entrypoint

EXPOSE 10000
ENTRYPOINT ["laravel-entrypoint"]
CMD ["apache2-foreground"]
