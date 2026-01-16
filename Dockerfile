FROM php:8.4-fpm

RUN apt-get update \
    && apt-get -y install libzip-dev zlib1g-dev git zip unzip libicu-dev g++ libbz2-dev libmemcached-dev libsqlite3-dev \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN docker-php-ext-configure zip && docker-php-ext-install pdo pdo_mysql pdo_sqlite zip bz2 intl

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

RUN chown -R www-data:www-data /var/www

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
