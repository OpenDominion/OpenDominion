#!/usr/bin/env sh

# Install Composer packages
composer install

# Copy the dotenv file
cp .env.example .env

# Generate application secret key
php artisan key:generate

# Generate IDE helper files
php artisan ide-helper:generate
php artisan ide-helper:models -N
php artisan ide-helper:meta

# Install NPM packages
yarn install

# Build frontend assets
yarn run dev

# Diagnostics check
php artisan self-diagnosis

# Migrate and seed the database
php artisan migrate --seed
