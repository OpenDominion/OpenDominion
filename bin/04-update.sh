#!/usr/bin/env sh

# Update Composer packages
composer install

# Generate new IDE helper files
php artisan ide-helper:generate
php artisan ide-helper:models -N
php artisan ide-helper:meta

# Update NPM packages
yarn install

# Rebuild frontend assets
yarn run dev

# Clear Laravel caches
php artisan optimize:clear

# Diagnostics check
php artisan self-diagnosis

# Migrate database if needed
php artisan migrate
