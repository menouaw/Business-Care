FROM php:8.1-fpm

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libzip-dev \
    zip \
    unzip \
    libicu-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure intl
RUN docker-php-ext-install pdo_mysql zip calendar intl

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist

COPY . .

RUN composer install --no-dev --no-interaction --optimize-autoloader --prefer-dist

RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000