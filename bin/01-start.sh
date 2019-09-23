#!/usr/bin/env sh

# Copy the Laradock configuration file
cp .env.laradock laradock/.env

# Build and start the Laradock Docker containers
cd laradock && docker-compose up --build -d workspace php-fpm nginx mariadb
