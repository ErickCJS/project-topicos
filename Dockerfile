FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends git unzip libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring bcmath exif gd zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf \
    && sed -ri 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf

WORKDIR /var/www/html
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000
CMD ["apache2-foreground"]
