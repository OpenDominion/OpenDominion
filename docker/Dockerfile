FROM node:18 AS node

# Everything shared by the runtime image and the benchmarking image. Split into
# stages rather than a second Dockerfile because a standalone file doing
# `FROM opendominion-app` would depend on build ordering, which Compose does not
# guarantee between services.
FROM php:8.5-apache AS base

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

# Profiling image, used only by the benchmark service.
#
# excimer is a sampling profiler: near-zero overhead, answers "where does wall
# time go". xdebug answers "how many times was this called", but slows
# execution by 10-50x, so benchmark.ini leaves it switched off and it is opted
# into per run with XDEBUG_MODE=profile.
#
# Deliberately NOT the last stage: a bare `docker build -f docker/Dockerfile .`
# builds whichever stage comes last, and that must be the runtime image. Anything
# wanting this one asks for it by name with `--target benchmark`.
FROM base AS benchmark

RUN pecl install excimer xdebug \
 && docker-php-ext-enable excimer xdebug

COPY docker/benchmark.ini /usr/local/etc/php/conf.d/zz-benchmark.ini

# The image the app service runs, and the default for any consumer that does not
# name a target. Identical to what this Dockerfile produced before it was split.
FROM base AS app
