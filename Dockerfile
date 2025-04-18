FROM php:8.1-fpm


WORKDIR /var/www/html



RUN apt-get update && apt-get install -y \
    git \
    curl \
    libzip-dev \
    zip \
    unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*



RUN docker-php-ext-install pdo_mysql zip


RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


COPY . /var/www/html




RUN composer install --no-interaction --optimize-autoloader --no-dev



RUN chown -R www-data:www-data /var/www/html


EXPOSE 9000 