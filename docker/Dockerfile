FROM node:18 AS node

FROM php:8.4-apache

# System dependencies for PHP extensions and tooling
RUN apt-get update && apt-get install -y \
    curl \
    git \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libonig-dev \
    libpng-dev \
    libzip-dev \
    unzip \
    zip \
  && rm -rf /var/lib/apt/lists/*

# PHP extensions required by composer.json
# curl, fileinfo, openssl are built into the base image already
RUN docker-php-ext-configure gd \
      --with-freetype \
      --with-jpeg \
  && docker-php-ext-install -j$(nproc) \
      bcmath \
      gd \
      mbstring \
      pdo_mysql \
      zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Node.js 18 copied from official image
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
  && ln -sf /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx \
  && npm install -g yarn

# Apache: enable mod_rewrite, point DocumentRoot to public/
RUN a2enmod rewrite
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
