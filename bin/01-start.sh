#!/usr/bin/env sh

# Copy the Laradock configuration files
cp .laradock/.env laradock/
cp -R .laradock/** laradock/

# Build and start the Laradock Docker containers
cd laradock && docker-compose up --build -d workspace php-fpm nginx mariadb
