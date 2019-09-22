FROM php:7.3-fpm-alpine

# Install MySQL extensions
RUN docker-php-ext-install -j$(nproc) bcmath pdo_mysql

# Install Xdebug
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install xdebug-2.7.2 \
    && docker-php-ext-enable xdebug \
    && apk del -f .build-deps

# Configure PHP
RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini \
    && echo 'xdebug.remote_enable=1' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo 'xdebug.remote_host=docker.for.win.localhost' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Install Composer
RUN curl --silent --show-error https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html
